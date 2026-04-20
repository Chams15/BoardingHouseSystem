import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';

type Ticket = {
    ticket_id: number;
    issue_desc: string;
    issue_photo_url: string | null;
    issue_photo_path?: string | null;
    priority: 'Low' | 'Medium' | 'High';
    status: 'Pending' | 'In Progress' | 'Resolved';
    contractor_notes: string | null;
    created_at: string;
    resolved_at: string | null;
    room: { room_id: number; room_number: string } | null;
    reporter: {
        user_id: number;
        email: string;
        tenant_profile?: { full_name: string };
    };
};

type RecurringByRoom = {
    room_id: number | null;
    room_label: string;
    total_tickets: number;
    tenant_count: number;
    open_tickets: number;
};

type RecurringByTenant = {
    tenant_id: number;
    tenant_name: string;
    tenant_email: string;
    total_tickets: number;
    affected_rooms: number;
    open_tickets: number;
};

type Props = {
    tickets: Ticket[];
    recurringByRoom: RecurringByRoom[];
    recurringByTenant: RecurringByTenant[];
    recurringWindowDays: number;
    filters: { status?: string };
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Maintenance', href: '/admin/maintenance' },
];

export default function AdminMaintenanceIndex({ tickets, recurringByRoom, recurringByTenant, recurringWindowDays, filters }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [statusFilter, setStatusFilter] = useState(filters.status ?? '');
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingTicket, setEditingTicket] = useState<Ticket | null>(null);

    const { data, setData, put, processing, errors, clearErrors } = useForm({
        priority: 'Medium' as 'Low' | 'Medium' | 'High',
        contractor_notes: '',
    });

    function applyFilter() {
        router.get('/admin/maintenance', { status: statusFilter || undefined }, { preserveState: true });
    }

    function openUpdateModal(ticket: Ticket) {
        setEditingTicket(ticket);
        setData({
            priority: ticket.priority,
            contractor_notes: ticket.contractor_notes ?? '',
        });
        clearErrors();
        setIsEditOpen(true);
    }

    function handleUpdateSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!editingTicket) {
            return;
        }

        put(`/admin/maintenance/${editingTicket.ticket_id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditOpen(false);
                setEditingTicket(null);
            },
        });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Maintenance Management" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Maintenance Tickets</h1>

                    <div className="flex items-center gap-2">
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                        <Button variant="outline" onClick={applyFilter}>Filter</Button>
                    </div>
                </div>

                {flash?.success && <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                {flash?.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 lg:col-span-2">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Date</th>
                                    <th className="px-4 py-3 font-medium">Reporter</th>
                                    <th className="px-4 py-3 font-medium">Room</th>
                                    <th className="px-4 py-3 font-medium">Issue</th>
                                    <th className="px-4 py-3 font-medium">Photo</th>
                                    <th className="px-4 py-3 font-medium">Priority</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y dark:divide-neutral-800">
                                {tickets.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No tickets found.</td>
                                    </tr>
                                ) : (
                                    tickets.map((ticket) => (
                                        <tr key={ticket.ticket_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{new Date(ticket.created_at).toLocaleDateString()}</td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {ticket.reporter.tenant_profile?.full_name ?? ticket.reporter.email}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{ticket.room ? ticket.room.room_number : 'Common'}</td>
                                            <td className="px-4 py-3 text-gray-700 dark:text-gray-200">{ticket.issue_desc}</td>
                                            <td className="px-4 py-3">
                                                {ticket.issue_photo_url ? (
                                                    <a href={ticket.issue_photo_url} target="_blank" rel="noreferrer" title="Open issue photo">
                                                        <img src={ticket.issue_photo_url} alt="Issue" className="h-10 w-10 rounded-md object-cover transition-transform hover:scale-105" />
                                                    </a>
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
                                            <td className="px-4 py-3 text-right">
                                                <Button size="sm" variant="ghost" onClick={() => openUpdateModal(ticket)}>
                                                    Update
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Recurring Patterns</h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Repeated maintenance activity in the past {recurringWindowDays} days (2+ tickets).
                        </p>

                        <div className="mt-4 space-y-4">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">By Room</h3>
                                <div className="mt-2 space-y-3">
                                    {recurringByRoom.length === 0 ? (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">No room hotspots in this timeframe.</p>
                                    ) : (
                                        recurringByRoom.map((item, idx) => (
                                            <div key={`${item.room_id ?? 'common'}-${idx}`} className="rounded-lg border p-3 dark:border-neutral-700">
                                                <p className="text-sm font-medium text-gray-700 dark:text-gray-200">{item.room_id ? `Room ${item.room_label}` : item.room_label}</p>
                                                <p className="mt-1 text-xs text-orange-600 dark:text-orange-400">{item.total_tickets} tickets</p>
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {item.tenant_count} tenants reported • {item.open_tickets} open
                                                </p>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>

                            <div className="border-t pt-4 dark:border-neutral-800">
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">By Tenant</h3>
                                <div className="mt-2 space-y-3">
                                    {recurringByTenant.length === 0 ? (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">No repeated reporter patterns in this timeframe.</p>
                                    ) : (
                                        recurringByTenant.map((item) => (
                                            <div key={item.tenant_id} className="rounded-lg border p-3 dark:border-neutral-700">
                                                <p className="text-sm font-medium text-gray-700 dark:text-gray-200">{item.tenant_name}</p>
                                                <p className="mt-1 text-xs text-orange-600 dark:text-orange-400">{item.total_tickets} tickets</p>
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {item.affected_rooms} rooms affected • {item.open_tickets} open
                                                </p>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Update Ticket</DialogTitle>
                            <DialogDescription>
                                {editingTicket ? `Editing ticket #${editingTicket.ticket_id}` : 'Update maintenance ticket details.'}
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleUpdateSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="priority" className="text-gray-700 dark:text-gray-300">Priority</Label>
                                <select
                                    id="priority"
                                    value={data.priority}
                                    onChange={(e) => setData('priority', e.target.value as 'Low' | 'Medium' | 'High')}
                                    className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-100"
                                >
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                                <InputError message={errors.priority} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contractor_notes" className="text-gray-700 dark:text-gray-300">Contractor Notes</Label>
                                <textarea
                                    id="contractor_notes"
                                    value={data.contractor_notes}
                                    onChange={(e) => setData('contractor_notes', e.target.value)}
                                    rows={4}
                                    className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-gray-100"
                                    placeholder="Add your reply/update for the tenant."
                                />
                                <InputError message={errors.contractor_notes} />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)} disabled={processing}>
                                    Cancel
                                </Button>
                                <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminLayout>
    );
}
