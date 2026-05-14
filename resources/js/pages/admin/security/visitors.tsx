import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import SecurityNav from './security-nav';

type Tenant = {
    user_id: number;
    email: string;
    tenant_profile?: { full_name: string };
};

type VisitorLog = {
    log_id: number;
    visitor_name: string;
    visitor_photo_url: string | null;
    purpose: string | null;
    time_in: string;
    time_out: string | null;
    tenant: Tenant;
};

type Props = {
    visitorLogs: VisitorLog[];
    tenants: Tenant[];
    filters: { search: string };
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Security', href: '/admin/security/visitors' },
    { title: 'Visitors', href: '/admin/security/visitors' },
];

export default function AdminSecurityVisitors({ visitorLogs, tenants, filters }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [search, setSearch] = useState(filters.search ?? '');
    const [tenantVisited, setTenantVisited] = useState<string>(tenants[0]?.user_id?.toString() ?? '');
    const [visitorName, setVisitorName] = useState('');
    const [purpose, setPurpose] = useState('');
    const [visitorPhoto, setVisitorPhoto] = useState<File | null>(null);

    const activeVisitors = useMemo(() => visitorLogs.filter((v) => v.time_out === null), [visitorLogs]);

    function refreshResults(nextSearch = search) {
        router.get(
            '/admin/security/visitors',
            { search: nextSearch },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function submitVisitor(e: React.FormEvent) {
        e.preventDefault();
        router.post(
            '/admin/security/visitors',
            {
                tenant_visited: Number(tenantVisited),
                visitor_name: visitorName,
                purpose,
                visitor_photo: visitorPhoto,
            },
            {
                forceFormData: true,
                onSuccess: () => {
                    setVisitorName('');
                    setPurpose('');
                    setVisitorPhoto(null);
                },
            },
        );
    }

    function checkoutVisitor(logId: number) {
        router.post(`/admin/security/visitors/${logId}/checkout`);
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Security - Visitors" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Security - Visitors</h1>
                <SecurityNav current="visitors" />

                <div className="grid gap-3 rounded-xl border bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            value={search}
                            onChange={(e) => {
                                const next = e.target.value;
                                setSearch(next);
                                refreshResults(next);
                            }}
                            placeholder="Search visitor logs by visitor, tenant, or purpose"
                            className="pl-9"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            setSearch('');
                            refreshResults('');
                        }}
                    >
                        Clear filters
                    </Button>
                </div>

                {flash?.success && <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                {flash?.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

                <div className="grid gap-4 lg:grid-cols-2">
                    <form onSubmit={submitVisitor} className="space-y-3 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Log Visitor Entry</h2>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Visited</label>
                            <select
                                value={tenantVisited}
                                onChange={(e) => setTenantVisited(e.target.value)}
                                required
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            >
                                {tenants.map((tenant) => (
                                    <option key={tenant.user_id} value={tenant.user_id}>
                                        {tenant.tenant_profile?.full_name ?? tenant.email}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Visitor Name</label>
                            <input
                                value={visitorName}
                                onChange={(e) => setVisitorName(e.target.value)}
                                required
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Purpose</label>
                            <input
                                value={purpose}
                                onChange={(e) => setPurpose(e.target.value)}
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Visitor Photo (optional)</label>
                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                onChange={(e) => setVisitorPhoto(e.target.files?.[0] ?? null)}
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-4 file:rounded-md file:border-0 file:bg-orange-100 file:px-3 file:py-1 file:text-orange-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            />
                        </div>

                        <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600">Log Entry</Button>
                    </form>

                    <div className="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Active Visitors</h2>
                        <div className="mt-3 space-y-2">
                            {activeVisitors.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">No active visitors.</p>
                            ) : (
                                activeVisitors.map((v) => (
                                    <div key={v.log_id} className="rounded-lg border p-2 text-sm dark:border-neutral-700">
                                        <p className="font-medium text-gray-800 dark:text-gray-200">{v.visitor_name}</p>
                                        <p className="text-gray-500 dark:text-gray-400">{v.tenant.tenant_profile?.full_name ?? v.tenant.email}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="border-b px-4 py-3 font-semibold text-gray-900 dark:border-neutral-800 dark:text-gray-100">Visitor Logs</div>
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Photo</th>
                                <th className="px-4 py-3 font-medium">Visitor</th>
                                <th className="px-4 py-3 font-medium">Tenant</th>
                                <th className="px-4 py-3 font-medium">Purpose</th>
                                <th className="px-4 py-3 font-medium">Time In</th>
                                <th className="px-4 py-3 font-medium">Time Out</th>
                                <th className="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {visitorLogs.length === 0 ? (
                                <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No visitor logs.</td></tr>
                            ) : (
                                visitorLogs.map((log) => (
                                    <tr key={log.log_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3">
                                            {log.visitor_photo_url ? (
                                                <a href={log.visitor_photo_url} target="_blank" rel="noreferrer" title="Open visitor photo">
                                                    <img
                                                        src={log.visitor_photo_url}
                                                        alt={log.visitor_name}
                                                        className="h-10 w-10 rounded-md object-cover transition-transform hover:scale-105"
                                                    />
                                                </a>
                                            ) : (
                                                <div className="h-10 w-10 rounded-md bg-gray-100 dark:bg-neutral-800" />
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-200">{log.visitor_name}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.tenant.tenant_profile?.full_name ?? log.tenant.email}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.purpose ?? '—'}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{new Date(log.time_in).toLocaleString()}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.time_out ? new Date(log.time_out).toLocaleString() : '—'}</td>
                                        <td className="px-4 py-3 text-right">
                                            {!log.time_out && (
                                                <Button size="sm" variant="ghost" onClick={() => checkoutVisitor(log.log_id)}>
                                                    Check Out
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
