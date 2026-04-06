<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        $logs = AuditLog::with('actor')
            ->orderByDesc('log_id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
        ]);
    }

    public function rollback(AuditLog $auditLog): RedirectResponse
    {
        if (! in_array($auditLog->event_type, ['created', 'updated', 'deleted'], true)) {
            return back()->with('error', 'Only created, updated, or deleted events can be rolled back.');
        }

        $alreadyRolledBack = AuditLog::where('rollback_of_log_id', $auditLog->log_id)->exists();
        if ($alreadyRolledBack) {
            return back()->with('error', 'This change has already been rolled back.');
        }

        try {
            DB::transaction(function () use ($auditLog) {
                AuditLogger::withoutLogging(function () use ($auditLog) {
                    $table = $auditLog->table_name;
                    $pkColumn = $auditLog->record_pk_column;
                    $pkValue = $auditLog->record_pk;

                    if ($auditLog->event_type === 'created') {
                        DB::table($table)->where($pkColumn, $pkValue)->delete();
                    }

                    if ($auditLog->event_type === 'updated') {
                        $oldValues = $auditLog->old_values ?? [];
                        unset($oldValues[$pkColumn]);

                        DB::table($table)
                            ->where($pkColumn, $pkValue)
                            ->update($oldValues);
                    }

                    if ($auditLog->event_type === 'deleted') {
                        $oldValues = $auditLog->old_values ?? [];
                        DB::table($table)->insert($oldValues);
                    }
                });

                AuditLog::create([
                    'actor_user_id' => auth()->user()?->user_id,
                    'event_type' => 'rolled_back',
                    'table_name' => $auditLog->table_name,
                    'record_pk_column' => $auditLog->record_pk_column,
                    'record_pk' => $auditLog->record_pk,
                    'old_values' => $auditLog->new_values,
                    'new_values' => $auditLog->old_values,
                    'action_meta' => ['rolled_back_event' => $auditLog->event_type],
                    'rollback_of_log_id' => $auditLog->log_id,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Rollback failed: '.$e->getMessage());
        }

        return back()->with('success', 'Rollback applied successfully.');
    }
}
