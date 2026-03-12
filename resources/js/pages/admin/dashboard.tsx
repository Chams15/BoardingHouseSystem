import { Head } from '@inertiajs/react';
import { Users, UserCheck, UserX, Home, DoorOpen, DoorClosed } from 'lucide-react';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    stats: {
        totalTenants: number;
        activeTenants: number;
        inactiveTenants: number;
        totalRooms: number;
        availableRooms: number;
        occupiedRooms: number;
    };
};

const breadcrumbs = [{ title: 'Admin', href: '/admin/dashboard' }, { title: 'Dashboard', href: '/admin/dashboard' }];

export default function AdminDashboard({ stats }: Props) {
    const cards = [
        { label: 'Total Tenants', value: stats.totalTenants, icon: Users, color: 'bg-blue-500' },
        { label: 'Active Tenants', value: stats.activeTenants, icon: UserCheck, color: 'bg-green-500' },
        { label: 'Inactive Tenants', value: stats.inactiveTenants, icon: UserX, color: 'bg-red-500' },
        { label: 'Total Rooms', value: stats.totalRooms, icon: Home, color: 'bg-purple-500' },
        { label: 'Available Rooms', value: stats.availableRooms, icon: DoorOpen, color: 'bg-emerald-500' },
        { label: 'Occupied Rooms', value: stats.occupiedRooms, icon: DoorClosed, color: 'bg-orange-500' },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => (
                        <div key={card.label} className="flex items-center gap-4 rounded-xl border bg-white p-5 shadow-sm">
                            <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${card.color}`}>
                                <card.icon className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{card.label}</p>
                                <p className="text-2xl font-bold text-gray-900">{card.value}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
