import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, ChevronLeft, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/admin-layout';
import RoomForm from './form';
import LeaseManager from '@/components/admin/lease-manager';
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
    category: string;
    price_monthly: string;
    capacity: number;
    status: 'Available' | 'Occupied' | 'Maintenance';
    amenities: string | null;
    room_image_url?: string | null;
    lease_contracts: LeaseContract[];
};

type Props = {
    room: Room;
    room_image_url: string | null;
};

export default function EditRoom({ room, room_image_url }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const breadcrumbs = [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Rooms', href: '/admin/rooms' },
        { title: `Edit Room ${room.room_number}`, href: '#' },
    ];

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/admin/rooms/${room.room_id}`, {
            onSuccess: () => {
                setShowDeleteDialog(false);
            },
            onFinish: () => {
                setIsDeleting(false);
            },
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Room ${room.room_number}`} />

            <div className="space-y-6">
                {/* Page Title */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Room {room.room_number}</h1>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5" />
                        <div>{flash.error}</div>
                    </div>
                )}

                {/* Top Actions */}
                <div className="flex items-center justify-between">
                    <Link href="/admin/rooms">
                        <Button variant="ghost" size="sm" className="text-gray-600 hover:text-gray-800 dark:text-gray-400">
                            <ChevronLeft className="mr-1 h-4 w-4" />
                            Back to Rooms
                        </Button>
                    </Link>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-red-600 hover:text-red-800 dark:text-red-400"
                        onClick={() => setShowDeleteDialog(true)}
                    >
                        <Trash2 className="mr-1 h-4 w-4" />
                        Delete Room
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Room Form */}
                    <div>
                        <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Room Details</h2>
                        <RoomForm room={room} roomImageUrl={room_image_url} onCancel={() => window.location.href = '/admin/rooms'} />
                    </div>

                    {/* Lease Management */}
                    <div>
                        <LeaseManager room={room} leases={room.lease_contracts} />
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Room</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete room {room.room_number}? This action cannot be undone, and the room must not have any active leases.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDeleteDialog(false)}
                            disabled={isDeleting}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={isDeleting}
                        >
                            {isDeleting ? 'Deleting...' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
