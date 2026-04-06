import { Link, router, usePage } from '@inertiajs/react';
import { Plus, Edit, Trash2, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useState } from 'react';

type Tenant = {
    user_id: number;
    email: string;
    tenant_profile?: {
        full_name: string;
        contact_number: string;
    };
};

type LeaseContract = {
    contract_id: number;
    tenant_id: number;
    room_id: number;
    start_date: string;
    end_date: string;
    security_deposit: string;
    contract_status: string;
    move_out_req_date: string | null;
    created_at: string;
    tenant: Tenant;
};

type Room = {
    room_id: number;
    room_number: string;
};

type Props = {
    room: Room;
    leases: LeaseContract[];
};

export default function LeaseManager({ room, leases }: Props) {
    const [deleteLeaseId, setDeleteLeaseId] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDeleteLease = () => {
        if (!deleteLeaseId) return;
        
        setIsDeleting(true);
        router.delete(`/admin/leases/${deleteLeaseId}`, {
            onSuccess: () => {
                setDeleteLeaseId(null);
            },
            onFinish: () => {
                setIsDeleting(false);
            },
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Active':
                return 'bg-green-100 text-green-700';
            case 'Pending_MoveOut':
                return 'bg-yellow-100 text-yellow-700';
            case 'Terminated':
                return 'bg-red-100 text-red-700';
            default:
                return 'bg-gray-100 text-gray-700';
        }
    };

    const getTenantName = (tenant: Tenant) => {
        return tenant.tenant_profile?.full_name || tenant.email;
    };

    return (
        <div className="rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Leases</h2>
                <Link href={`/admin/leases/create/${room.room_id}`}>
                    <Button size="sm" className="gap-1 bg-blue-600 hover:bg-blue-700 text-white">
                        <Plus className="h-4 w-4" />
                        New Lease
                    </Button>
                </Link>
            </div>

            {leases.length === 0 ? (
                <div className="text-center py-8">
                    <p className="text-gray-500 dark:text-gray-400 mb-4">No leases for this room</p>
                    <Link href={`/admin/leases/create/${room.room_id}`}>
                        <Button size="sm" className="gap-1 bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus className="h-4 w-4" />
                            Create First Lease
                        </Button>
                    </Link>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b dark:border-neutral-700">
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Tenant</th>
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Period</th>
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Deposit</th>
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Status</th>
                                <th className="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-700">
                            {leases.map((lease) => (
                                <tr key={lease.contract_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                    <td className="px-3 py-3 text-gray-900 dark:text-gray-100">
                                        {getTenantName(lease.tenant)}
                                        <div className="text-xs text-gray-500 dark:text-gray-400">{lease.tenant.email}</div>
                                    </td>
                                    <td className="px-3 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        <div>{lease.start_date}</div>
                                        <div className="text-xs">to {lease.end_date}</div>
                                    </td>
                                    <td className="px-3 py-3 text-gray-600 dark:text-gray-400">
                                        ₱{Number(lease.security_deposit).toLocaleString()}
                                    </td>
                                    <td className="px-3 py-3">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusColor(
                                                lease.contract_status
                                            )}`}
                                        >
                                            {lease.contract_status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <div className="flex gap-1 justify-end">
                                            <Link href={`/admin/leases/${lease.contract_id}/edit`}>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-red-600 hover:text-red-800 dark:text-red-400"
                                                onClick={() => setDeleteLeaseId(lease.contract_id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Delete Lease Dialog */}
            <Dialog open={deleteLeaseId !== null} onOpenChange={(open) => !open && setDeleteLeaseId(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Terminate Lease</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to terminate this lease? The room will be marked as available once the lease is terminated.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteLeaseId(null)}
                            disabled={isDeleting}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteLease}
                            disabled={isDeleting}
                        >
                            {isDeleting ? 'Terminating...' : 'Terminate Lease'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
