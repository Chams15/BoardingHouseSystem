import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Ticket = {
    ticket_id: number;
    issue_desc: string;
    issue_photo_url: string | null;
    priority: 'Low' | 'Medium' | 'High';
    status: 'Pending' | 'In Progress' | 'Resolved';
    contractor_notes: string | null;
    created_at: string;
    resolved_at: string | null;
    room: {
        room_id: number;
        room_number: string;
    } | null;
};

type ActiveContract = {
    room: {
        room_id: number;
        room_number: string;
    };
} | null;

type Props = {
    tickets: Ticket[];
    activeContract: ActiveContract;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Maintenance', href: '/maintenance' },
];

export default function MaintenanceIndex({ tickets, activeContract }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [issueDesc, setIssueDesc] = useState('');
    const [priority, setPriority] = useState<'Low' | 'Medium' | 'High'>('Medium');
    const [issuePhoto, setIssuePhoto] = useState<File | null>(null);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        router.post(
            '/maintenance',
            {
                issue_desc: issueDesc,
                priority,
                room_id: activeContract?.room.room_id ?? null,
                issue_photo: issuePhoto,
            },
            {
                forceFormData: true,
                onSuccess: () => {
                    setIssueDesc('');
                    setPriority('Medium');
                    setIssuePhoto(null);
                },
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Maintenance" />

            <div className="space-y-6 p-4">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Maintenance & Complaints</h1>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div>
                        <label htmlFor="issue_desc" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Describe your concern
                        </label>
                        <textarea
                            id="issue_desc"
                            value={issueDesc}
                            onChange={(e) => setIssueDesc(e.target.value)}
                            required
                            rows={4}
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-orange-400 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            placeholder="Ex: Faucet in CR is leaking continuously."
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="priority" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Priority
                            </label>
                            <select
                                id="priority"
                                value={priority}
                                onChange={(e) => setPriority(e.target.value as 'Low' | 'Medium' | 'High')}
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-orange-400 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            >
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>

                        <div>
                            <p className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Room</p>
                            <div className="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-300">
                                {activeContract?.room ? `Room ${activeContract.room.room_number}` : 'No assigned room'}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label htmlFor="issue_photo" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Issue Photo (optional)
                        </label>
                        <input
                            id="issue_photo"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            onChange={(e) => setIssuePhoto(e.target.files?.[0] ?? null)}
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-4 file:rounded-md file:border-0 file:bg-orange-100 file:px-3 file:py-1 file:text-orange-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                    </div>

                    <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600">
                        Submit Ticket
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Date</th>
                                <th className="px-4 py-3 font-medium">Issue</th>
                                <th className="px-4 py-3 font-medium">Photo</th>
                                <th className="px-4 py-3 font-medium">Priority</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Notes</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {tickets.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No maintenance tickets yet.
                                    </td>
                                </tr>
                            ) : (
                                tickets.map((ticket) => (
                                    <tr key={ticket.ticket_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{new Date(ticket.created_at).toLocaleDateString()}</td>
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-200">{ticket.issue_desc}</td>
                                        <td className="px-4 py-3">
                                            {ticket.issue_photo_url ? (
                                                <img src={ticket.issue_photo_url} alt="Issue" className="h-10 w-10 rounded-md object-cover" />
                                            ) : (
                                                <div className="h-10 w-10 rounded-md bg-gray-100 dark:bg-neutral-800" />
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{ticket.priority}</td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    ticket.status === 'Resolved'
                                                        ? 'bg-green-100 text-green-700'
                                                        : ticket.status === 'In Progress'
                                                          ? 'bg-blue-100 text-blue-700'
                                                          : 'bg-yellow-100 text-yellow-700'
                                                }`}
                                            >
                                                {ticket.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{ticket.contractor_notes ?? '—'}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
