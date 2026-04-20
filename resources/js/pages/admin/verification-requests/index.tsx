import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Check, Search, X } from 'lucide-react';
import { Fragment, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';

type VerificationRequest = {
    profile_id: number;
    verification_status: 'Pending' | 'Approved' | 'Rejected';
    verification_note: string | null;
    verification_submitted_at: string | null;
    verified_at: string | null;
    full_name: string;
    contact_number: string;
    contact_address: string | null;
    id_doc_url: string | null;
    user: {
        user_id: number;
        email: string;
    };
    verifier: {
        user_id: number;
        email: string;
    } | null;
};

type Filters = {
    search: string;
    status: string;
};

type Props = {
    requests: VerificationRequest[];
    filters: Filters;
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Verification Requests', href: '/admin/verification-requests' },
];

const statusOptions = [
    { label: 'All statuses', value: '' },
    { label: 'Pending', value: 'Pending' },
    { label: 'Approved', value: 'Approved' },
    { label: 'Rejected', value: 'Rejected' },
];

function statusClasses(status: VerificationRequest['verification_status']) {
    if (status === 'Approved') {
        return 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300';
    }

    if (status === 'Rejected') {
        return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
    }

    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300';
}

export default function VerificationRequestsIndex({ requests, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [expandedProfileId, setExpandedProfileId] = useState<number | null>(null);
    const [notes, setNotes] = useState<Record<number, string>>({});

    const activeCount = useMemo(() => [search, status].filter((value) => value.trim() !== '').length, [search, status]);

    function refreshResults(nextSearch = search, nextStatus = status) {
        router.get(
            '/admin/verification-requests',
            {
                search: nextSearch,
                status: nextStatus,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function handleApprove(profileId: number) {
        router.post(`/admin/verification-requests/${profileId}/approve`, {}, { preserveScroll: true });
    }

    function handleReject(profileId: number) {
        const note = notes[profileId]?.trim() ?? '';

        if (!note) {
            alert('Please add a rejection note.');
            return;
        }

        router.post(`/admin/verification-requests/${profileId}/reject`, { note }, { preserveScroll: true });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Verification Requests" />

            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Verification Requests</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Search tenants, filter by status, and expand each row for full review details.</p>
                    </div>
                    <div className="rounded-full border px-3 py-1 text-xs font-medium text-gray-600 dark:border-neutral-700 dark:text-gray-300">
                        {requests.length} result{requests.length === 1 ? '' : 's'}{activeCount > 0 ? ` · ${activeCount} filter${activeCount === 1 ? '' : 's'} active` : ''}
                    </div>
                </div>

                <div className="grid gap-3 rounded-xl border bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_180px_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            value={search}
                            onChange={(e) => {
                                const next = e.target.value;
                                setSearch(next);
                                refreshResults(next, status);
                            }}
                            placeholder="Search by name, email, contact number, or address"
                            className="pl-9"
                        />
                    </div>

                    <select
                        value={status}
                        onChange={(e) => {
                            const next = e.target.value;
                            setStatus(next);
                            refreshResults(search, next);
                        }}
                        className="h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                    >
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            setSearch('');
                            setStatus('');
                            refreshResults('', '');
                        }}
                    >
                        Clear filters
                    </Button>
                </div>

                {requests.length === 0 ? (
                    <div className="rounded-xl border bg-white p-8 text-center text-gray-500 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 dark:text-gray-400">
                        No verification requests matched the current filters.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-neutral-800">
                            <thead className="bg-gray-50 dark:bg-neutral-950/60">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tenant</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dates</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-neutral-800">
                                {requests.map((request) => {
                                    const expanded = expandedProfileId === request.profile_id;

                                    return (
                                        <Fragment key={request.profile_id}>
                                            <tr key={request.profile_id} className="align-top">
                                                <td className="px-4 py-4">
                                                    <div className="space-y-1">
                                                        <p className="font-medium text-gray-900 dark:text-gray-100">{request.full_name}</p>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">{request.user.email}</p>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                    <div className="space-y-1">
                                                        <p>{request.contact_number}</p>
                                                        <p className="max-w-xs break-words text-gray-500 dark:text-gray-400">{request.contact_address ?? 'No address provided'}</p>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClasses(request.verification_status)}`}>
                                                        {request.verification_status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                    <div className="space-y-1">
                                                        <p><span className="text-gray-500 dark:text-gray-400">Submitted:</span> {request.verification_submitted_at ? new Date(request.verification_submitted_at).toLocaleString() : '—'}</p>
                                                        <p><span className="text-gray-500 dark:text-gray-400">Verified:</span> {request.verified_at ? new Date(request.verified_at).toLocaleString() : '—'}</p>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setExpandedProfileId(expanded ? null : request.profile_id)}
                                                    >
                                                        {expanded ? <ChevronUp className="mr-1 h-4 w-4" /> : <ChevronDown className="mr-1 h-4 w-4" />}
                                                        {expanded ? 'Hide details' : 'Show details'}
                                                    </Button>
                                                </td>
                                            </tr>
                                            {expanded && (
                                                <tr className="bg-gray-50/70 dark:bg-neutral-950/40">
                                                    <td colSpan={5} className="px-4 py-5">
                                                        <div className="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                                                            <div className="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                                                                <p><strong>Government ID:</strong> {request.id_doc_url ? <a href={request.id_doc_url} target="_blank" rel="noreferrer" className="text-blue-600 underline">Open uploaded ID</a> : '—'}</p>
                                                                <p><strong>Admin note:</strong> {request.verification_note ?? 'No note yet'}</p>
                                                                <p><strong>Reviewed by:</strong> {request.verifier ? `${request.verifier.email} (user ${request.verifier.user_id})` : '—'}</p>
                                                            </div>

                                                            <div className="space-y-3">
                                                                <textarea
                                                                    placeholder="Add rejection note (required when denying)..."
                                                                    value={notes[request.profile_id] ?? ''}
                                                                    onChange={(e) => setNotes((prev) => ({ ...prev, [request.profile_id]: e.target.value }))}
                                                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                                                                    rows={3}
                                                                />
                                                                <div className="flex flex-wrap gap-2">
                                                                    <Button
                                                                        size="sm"
                                                                        className="bg-green-600 text-white hover:bg-green-700"
                                                                        onClick={() => handleApprove(request.profile_id)}
                                                                    >
                                                                        <Check className="mr-1 h-4 w-4" />
                                                                        Approve
                                                                    </Button>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        className="border-red-300 text-red-600 hover:bg-red-50"
                                                                        onClick={() => handleReject(request.profile_id)}
                                                                    >
                                                                        <X className="mr-1 h-4 w-4" />
                                                                        Deny
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            )}
                                        </Fragment>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
