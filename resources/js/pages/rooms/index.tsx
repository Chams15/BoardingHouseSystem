import { Head, router, usePage } from '@inertiajs/react';
import { DoorOpen, Users, Wifi } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Room = {
    room_id: number;
    room_number: string;
    category: string;
    price_monthly: string;
    capacity: number;
    status: 'Available' | 'Occupied' | 'Maintenance';
    amenities: string | null;
    room_requests_count: number;
};

type Props = {
    rooms: Room[];
    userPendingRequests: Record<number, number>; // room_id -> request_id
    hasActiveContract: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Rooms', href: '/rooms' },
];

export default function RoomsIndex({ rooms, userPendingRequests, hasActiveContract }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    function handleRequest(roomId: number) {
        router.post(`/rooms/${roomId}/request`, {}, { preserveState: true });
    }

    function handleCancel(roomId: number) {
        router.delete(`/rooms/requests/${roomId}/cancel`, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rooms" />

            <div className="p-4 space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Available Rooms</h1>

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

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {rooms.map((room) => {
                        const isAvailable = room.status === 'Available';
                        const pendingRequestId = userPendingRequests[room.room_id];

                        return (
                            <div
                                key={room.room_id}
                                className="flex flex-col rounded-xl border bg-white shadow-sm dark:bg-neutral-900 dark:border-neutral-800"
                            >
                                <div className="p-5 space-y-3 flex-1">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            Room {room.room_number}
                                        </h3>
                                        <span
                                            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                isAvailable
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700'
                                            }`}
                                        >
                                            {room.status}
                                        </span>
                                    </div>

                                    <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <p className="flex items-center gap-2">
                                            <DoorOpen className="h-4 w-4" />
                                            {room.category}
                                        </p>
                                        <p className="flex items-center gap-2">
                                            <Users className="h-4 w-4" />
                                            Capacity: {room.capacity}
                                        </p>
                                        {room.amenities && (
                                            <p className="flex items-center gap-2">
                                                <Wifi className="h-4 w-4" />
                                                {room.amenities}
                                            </p>
                                        )}
                                    </div>

                                    <p className="text-xl font-bold text-orange-600">
                                        ₱{Number(room.price_monthly).toLocaleString()}<span className="text-sm font-normal text-gray-500">/mo</span>
                                    </p>

                                    {isAvailable && room.room_requests_count > 0 && (
                                        <p className="text-xs text-gray-500">
                                            {room.room_requests_count} pending request{room.room_requests_count !== 1 ? 's' : ''}
                                        </p>
                                    )}
                                </div>

                                <div className="border-t p-4 dark:border-neutral-800">
                                    {isAvailable ? (
                                        pendingRequestId ? (
                                            <div className="flex gap-2">
                                                <Button disabled className="flex-1" variant="outline">
                                                    Request Pending
                                                </Button>
                                                <Button
                                                    onClick={() => handleCancel(pendingRequestId)}
                                                    variant="destructive"
                                                    className="shrink-0"
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        ) : hasActiveContract ? (
                                            <Button disabled className="w-full" variant="outline">
                                                Already Assigned a Room
                                            </Button>
                                        ) : (
                                            <Button
                                                onClick={() => handleRequest(room.room_id)}
                                                className="w-full bg-orange-500 hover:bg-orange-600 text-white"
                                            >
                                                Request to Occupy
                                            </Button>
                                        )
                                    ) : (
                                        <Button disabled className="w-full" variant="outline">
                                            Occupied
                                        </Button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
