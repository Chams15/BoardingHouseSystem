import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import RoomForm from './form';

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Rooms', href: '/admin/rooms' },
    { title: 'Create Room', href: '#' },
];

export default function CreateRoom() {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Room" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Create New Room</h1>
            </div>

            <RoomForm />
        </AdminLayout>
    );
}
