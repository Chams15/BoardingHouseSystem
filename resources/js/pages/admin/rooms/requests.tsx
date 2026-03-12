import { Head, router, usePage } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

type RoomRequestItem = {
    request_id: number;
    status: string;
    message: string | null;
    created_at: string;
    room: {
        room_id: number;
        room_number: string;
        category: string;
        price_monthly: string;
    };
    user: {
        user_id: number;
        email: string;
        tenant_profile?: {
            full_name: string;
            contact_number: string;
        };
    };
};

type Props = {
    requests: RoomRequestItem[];
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Rooms', href: '/admin/rooms' },
    { title: 'Requests', href: '/admin/rooms/requests' },
];

export default function RoomRequests({ requests }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string } | undefined;

    function handleApprove(requestId: number) {
        router.post(`/admin/rooms/requests/${requestId}/approve`);
    }

    function handleReject(requestId: number) {
        router.post(`/admin/rooms/requests/${requestId}/reject`);
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Room Requests" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Room Requests</h1>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                {requests.length === 0 ? (
                    <div className="rounded-xl border bg-white p-8 text-center text-gray-500 shadow-sm">
                        No pending room requests.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-gray-50 text-gray-600">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Tenant</th>
                                    <th className="px-4 py-3 font-medium">Email</th>
                                    <th className="px-4 py-3 font-medium">Room</th>
                                    <th className="px-4 py-3 font-medium">Category</th>
                                    <th className="px-4 py-3 font-medium">Date</th>
                                    <th className="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {requests.map((req) => (
                                    <tr key={req.request_id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {req.user.tenant_profile?.full_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{req.user.email}</td>
                                        <td className="px-4 py-3 text-gray-600">{req.room.room_number}</td>
                                        <td className="px-4 py-3 text-gray-600">{req.room.category}</td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {new Date(req.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    size="sm"
                                                    className="bg-green-600 hover:bg-green-700 text-white"
                                                    onClick={() => handleApprove(req.request_id)}
                                                >
                                                    <Check className="mr-1 h-4 w-4" />
                                                    Approve
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="text-red-600 border-red-200 hover:bg-red-50"
                                                    onClick={() => handleReject(req.request_id)}
                                                >
                                                    <X className="mr-1 h-4 w-4" />
                                                    Reject
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
