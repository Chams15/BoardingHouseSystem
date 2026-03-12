import { Head, Link, router, usePage } from '@inertiajs/react';
import { UserMinus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

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
    contract_status: string;
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
    lease_contracts: LeaseContract[];
    room_requests_count: number;
};

type Props = {
    rooms: Room[];
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Rooms', href: '/admin/rooms' },
];

export default function AdminRoomsIndex({ rooms }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string } | undefined;

    function handleRemoveTenant(roomId: number) {
        if (confirm('Are you sure you want to remove the tenant from this room?')) {
            router.post(`/admin/rooms/${roomId}/remove-tenant`);
        }
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Rooms Management" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Rooms</h1>
                    <Link href="/admin/rooms/requests">
                        <Button className="bg-orange-500 hover:bg-orange-600 text-white">
                            View Requests
                            {rooms.reduce((sum, r) => sum + r.room_requests_count, 0) > 0 && (
                                <span className="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-orange-600">
                                    {rooms.reduce((sum, r) => sum + r.room_requests_count, 0)}
                                </span>
                            )}
                        </Button>
                    </Link>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th className="px-4 py-3 font-medium">Room</th>
                                <th className="px-4 py-3 font-medium">Category</th>
                                <th className="px-4 py-3 font-medium">Price</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Tenant</th>
                                <th className="px-4 py-3 font-medium">Requests</th>
                                <th className="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {rooms.map((room) => {
                                const activeLease = room.lease_contracts.find(
                                    (lc) => lc.contract_status === 'Active',
                                );

                                return (
                                    <tr key={room.room_id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {room.room_number}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{room.category}</td>
                                        <td className="px-4 py-3 text-gray-600">
                                            ₱{Number(room.price_monthly).toLocaleString()}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    room.status === 'Available'
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-red-100 text-red-700'
                                                }`}
                                            >
                                                {room.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {activeLease
                                                ? activeLease.tenant.tenant_profile?.full_name ?? activeLease.tenant.email
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {room.room_requests_count > 0 ? (
                                                <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-100 px-1.5 text-xs font-medium text-orange-700">
                                                    {room.room_requests_count}
                                                </span>
                                            ) : (
                                                '0'
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {activeLease && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-red-500 hover:text-red-700"
                                                    onClick={() => handleRemoveTenant(room.room_id)}
                                                >
                                                    <UserMinus className="mr-1 h-4 w-4" />
                                                    Remove
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
