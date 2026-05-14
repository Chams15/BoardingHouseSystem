import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import SecurityNav from './security-nav';

type Tenant = {
    user_id: number;
    email: string;
    tenant_profile?: { full_name: string };
};

type Incident = {
    incident_id: number;
    title: string;
    description: string;
    severity: 'Low' | 'Medium' | 'High';
    status: 'Open' | 'Investigating' | 'Resolved';
    created_at: string;
    reporter?: Tenant;
};

type Props = {
    incidents: Incident[];
    filters: { search: string };
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Security', href: '/admin/security/incidents' },
    { title: 'Incidents', href: '/admin/security/incidents' },
];

export default function AdminSecurityIncidents({ incidents, filters }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [search, setSearch] = useState(filters.search ?? '');
    const [incidentTitle, setIncidentTitle] = useState('');
    const [incidentDesc, setIncidentDesc] = useState('');
    const [severity, setSeverity] = useState<'Low' | 'Medium' | 'High'>('Medium');

    function refreshResults(nextSearch = search) {
        router.get(
            '/admin/security/incidents',
            { search: nextSearch },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function submitIncident(e: React.FormEvent) {
        e.preventDefault();
        router.post(
            '/admin/security/incidents',
            {
                title: incidentTitle,
                description: incidentDesc,
                severity,
            },
            {
                onSuccess: () => {
                    setIncidentTitle('');
                    setIncidentDesc('');
                    setSeverity('Medium');
                },
            },
        );
    }

    function updateIncidentStatus(incidentId: number, status: Incident['status']) {
        router.put(`/admin/security/incidents/${incidentId}`, { status });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Security - Incidents" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Security - Incidents</h1>
                <SecurityNav current="incidents" />

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
                            placeholder="Search incidents by title, description, severity, status, or reporter"
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

                <form onSubmit={submitIncident} className="space-y-3 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Record Security Incident</h2>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input
                            value={incidentTitle}
                            onChange={(e) => setIncidentTitle(e.target.value)}
                            required
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea
                            value={incidentDesc}
                            onChange={(e) => setIncidentDesc(e.target.value)}
                            rows={3}
                            required
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Severity</label>
                        <select
                            value={severity}
                            onChange={(e) => setSeverity(e.target.value as 'Low' | 'Medium' | 'High')}
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        >
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600">Record Incident</Button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="border-b px-4 py-3 font-semibold text-gray-900 dark:border-neutral-800 dark:text-gray-100">Security Incidents</div>
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Title</th>
                                <th className="px-4 py-3 font-medium">Severity</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {incidents.length === 0 ? (
                                <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No incidents logged.</td></tr>
                            ) : (
                                incidents.map((incident) => (
                                    <tr key={incident.incident_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-200">{incident.title}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{incident.severity}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                incident.status === 'Resolved'
                                                    ? 'bg-green-100 text-green-700'
                                                    : incident.status === 'Investigating'
                                                      ? 'bg-blue-100 text-blue-700'
                                                      : 'bg-yellow-100 text-yellow-700'
                                            }`}>
                                                {incident.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <select
                                                value={incident.status}
                                                onChange={(e) => updateIncidentStatus(incident.incident_id, e.target.value as Incident['status'])}
                                                className="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-100"
                                            >
                                                <option value="Open">Open</option>
                                                <option value="Investigating">Investigating</option>
                                                <option value="Resolved">Resolved</option>
                                            </select>
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
