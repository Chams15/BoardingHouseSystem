import { Head, Link, router, usePage } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

type AuditLogItem = {
    audit_log_id: number;
    event_type: 'created' | 'updated' | 'deleted' | 'rolled_back' | 'sql_query';
    table_name: string;
    record_pk: string;
    created_at: string;
    actor?: {
        user_id: number;
        email: string;
    };
    rollback_of_audit_log_id?: number | null;
};

type PaginatedLogs = {
    data: AuditLogItem[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    logs: PaginatedLogs;
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Audit Logs', href: '/admin/audit-logs' },
];

const eventClass: Record<string, string> = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
    rolled_back: 'bg-orange-100 text-orange-700',
    sql_query: 'bg-violet-100 text-violet-700',
};

export default function AdminAuditLogsIndex({ logs }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    function handleRollback(logId: number) {
        if (confirm('Rollback this change? This action will write a compensating change to the database.')) {
            router.post(`/admin/audit-logs/${logId}/rollback`);
        }
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Audit Logs</h1>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 dark:bg-neutral-800 dark:border-neutral-700 text-gray-600 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">When</th>
                                <th className="px-4 py-3 font-medium">Actor</th>
                                <th className="px-4 py-3 font-medium">Event</th>
                                <th className="px-4 py-3 font-medium">Table</th>
                                <th className="px-4 py-3 font-medium">Record</th>
                                <th className="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No audit logs yet.
                                    </td>
                                </tr>
                            ) : (
                                logs.data.map((log) => {
                                    const canRollback = ['created', 'updated', 'deleted'].includes(log.event_type);
                                    return (
                                        <tr key={log.audit_log_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {new Date(log.created_at).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {log.actor?.email ?? 'System'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${eventClass[log.event_type] ?? ''}`}>
                                                    {log.event_type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.table_name}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.record_pk}</td>
                                            <td className="px-4 py-3 text-right">
                                                {canRollback ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-orange-600 hover:text-orange-800"
                                                        onClick={() => handleRollback(log.audit_log_id)}
                                                    >
                                                        <RotateCcw className="mr-1 h-4 w-4" />
                                                        Rollback
                                                    </Button>
                                                ) : (
                                                    <span className="text-xs text-gray-400">-</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                {logs.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {logs.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                className={`rounded-lg px-3 py-1.5 text-sm ${
                                    link.active
                                        ? 'bg-orange-500 text-white'
                                        : link.url
                                          ? 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-neutral-800'
                                          : 'text-gray-300 cursor-default'
                                }`}
                                preserveState
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
