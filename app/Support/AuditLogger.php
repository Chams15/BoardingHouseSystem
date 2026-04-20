<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    protected static bool $enabled = true;

    protected const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    public static function withoutLogging(callable $callback): mixed
    {
        $previous = self::$enabled;
        self::$enabled = false;

        try {
            return $callback();
        } finally {
            self::$enabled = $previous;
        }
    }

    public static function logModelEvent(
        string $eventType,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $actionMeta = null,
        ?int $rollbackOfLogId = null
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        if ($model instanceof AuditLog) {
            return;
        }

        $pkName = $model->getKeyName();
        $pkValue = $model->getAttribute($pkName) ?? $model->getKey();

        if ($pkValue === null) {
            return;
        }

        AuditLog::create([
            'actor_user_id' => auth()->user()?->user_id,
            'event_type' => $eventType,
            'table_name' => $model->getTable(),
            'record_pk_column' => $pkName,
            'record_pk' => (string) $pkValue,
            'old_values' => self::sanitize($oldValues),
            'new_values' => self::sanitize($newValues),
            'action_meta' => $actionMeta,
            'rollback_of_audit_log_id' => $rollbackOfLogId,
        ]);
    }

    public static function logSqlQuery(
        string $sql,
        array $bindings,
        float|int $timeMs,
        ?string $connectionName = null
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        $tableName = self::detectTableName($sql) ?? '__raw_sql__';

        AuditLog::create([
            'actor_user_id' => auth()->user()?->user_id,
            'event_type' => 'sql_query',
            'table_name' => $tableName,
            'record_pk_column' => '__raw__',
            'record_pk' => substr(hash('sha256', $sql.'|'.json_encode($bindings).'|'.microtime(true)), 0, 32),
            'old_values' => null,
            'new_values' => null,
            'action_meta' => [
                'sql' => mb_substr($sql, 0, 5000),
                'bindings' => self::sanitizeBindings($bindings),
                'time_ms' => $timeMs,
                'connection' => $connectionName,
            ],
            'rollback_of_audit_log_id' => null,
        ]);
    }

    protected static function detectTableName(string $sql): ?string
    {
        $patterns = [
            '/^\s*insert\s+into\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*update\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*delete\s+from\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*replace\s+into\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*alter\s+table\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*truncate\s+table\s+`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*drop\s+table\s+(if\s+exists\s+)?`?([a-zA-Z0-9_]+)`?/i',
            '/^\s*create\s+table\s+(if\s+not\s+exists\s+)?`?([a-zA-Z0-9_]+)`?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches) === 1) {
                return $matches[count($matches) - 1] ?? null;
            }
        }

        return null;
    }

    protected static function sanitizeBindings(array $bindings): array
    {
        return array_map(static function ($value) {
            if (is_string($value) && strlen($value) > 2000) {
                return substr($value, 0, 2000).'...[truncated]';
            }

            return $value;
        }, $bindings);
    }

    protected static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}
