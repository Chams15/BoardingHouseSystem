<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $visitorLogs = VisitorLog::with('tenant.tenantProfile')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('visitor_name', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('tenant.tenantProfile', function ($tenantProfileQuery) use ($search): void {
                            $tenantProfileQuery->where('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('tenant', function ($tenantQuery) use ($search): void {
                            $tenantQuery->where('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('time_in')
            ->get()
            ->map(function (VisitorLog $log) {
                return [
                    'log_id' => $log->log_id,
                    'visitor_name' => $log->visitor_name,
                    'visitor_photo_url' => $log->visitor_photo_path ? Storage::url($log->visitor_photo_path) : null,
                    'visitor_photo_path' => $log->visitor_photo_path,
                    'purpose' => $log->purpose,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                    'tenant' => $log->tenant,
                ];
            });

        $incidents = SecurityIncident::with('reporter.tenantProfile')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('severity', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('reporter.tenantProfile', function ($tenantProfileQuery) use ($search): void {
                            $tenantProfileQuery->where('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('reporter', function ($reporterQuery) use ($search): void {
                            $reporterQuery->where('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $blacklist = Blacklist::orderByDesc('banned_at')->get();

        $tenants = User::where('role', 'Tenant')
            ->with('tenantProfile')
            ->orderBy('email')
            ->get(['user_id', 'email']);

        return Inertia::render('admin/security/index', [
            'visitorLogs' => $visitorLogs,
            'incidents' => $incidents,
            'blacklist' => $blacklist,
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function storeVisitor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_visited' => [
                'required',
                'integer',
                Rule::exists('users', 'user_id')->where(fn ($q) => $q->where('role', 'Tenant')),
            ],
            'visitor_name' => ['required', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'visitor_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $photoPath = $request->file('visitor_photo')?->store('visitors', 'public');

        VisitorLog::create([
            'tenant_visited' => $validated['tenant_visited'],
            'visitor_name' => $validated['visitor_name'],
            'visitor_photo_path' => $photoPath,
            'purpose' => $validated['purpose'] ?? null,
            'time_in' => now(),
        ]);

        return back()->with('success', 'Visitor logged successfully.');
    }

    public function checkOutVisitor(VisitorLog $visitorLog): RedirectResponse
    {
        if ($visitorLog->time_out !== null) {
            return back()->with('error', 'Visitor is already checked out.');
        }

        $visitorLog->update([
            'time_out' => now(),
        ]);

        return back()->with('success', 'Visitor checked out.');
    }

    public function storeIncident(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'severity' => ['required', 'in:Low,Medium,High'],
        ]);

        SecurityIncident::create([
            'reported_by' => $request->user()->user_id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => 'Open',
        ]);

        return back()->with('success', 'Security incident recorded.');
    }

    public function updateIncident(Request $request, SecurityIncident $incident): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Open,Investigating,Resolved'],
        ]);

        $incident->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'Resolved' ? now() : null,
        ]);

        return back()->with('success', 'Incident status updated.');
    }

    public function addToBlacklist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100', 'unique:blacklist,email'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        Blacklist::create([
            'email' => $validated['email'],
            'reason' => $validated['reason'],
            'banned_at' => now(),
        ]);

        return back()->with('success', 'Email added to blacklist.');
    }

    public function removeFromBlacklist(Blacklist $blacklist): RedirectResponse
    {
        $blacklist->delete();

        return back()->with('success', 'Removed from blacklist.');
    }
}
