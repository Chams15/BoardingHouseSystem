import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import type { User } from '@/types';

type Props = {
    tenant: User;
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Edit', href: '#' },
];

export default function EditTenant({ tenant }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        full_name: tenant.tenant_profile?.full_name ?? '',
        email: tenant.email,
        contact_number: tenant.tenant_profile?.contact_number ?? '',
        emergency_contact: tenant.tenant_profile?.emergency_contact ?? '',
        is_active: tenant.is_active,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/admin/tenants/${tenant.user_id}`);
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Tenant" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Edit Tenant</h1>

                <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border bg-white p-6 shadow-sm">
                    <div className="grid gap-2">
                        <Label htmlFor="full_name">Full Name</Label>
                        <Input
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            required
                            className="bg-white"
                        />
                        <InputError message={errors.full_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="bg-white"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_number">Contact Number</Label>
                        <Input
                            id="contact_number"
                            type="tel"
                            value={data.contact_number}
                            onChange={(e) => setData('contact_number', e.target.value)}
                            required
                            className="bg-white"
                        />
                        <InputError message={errors.contact_number} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="emergency_contact">Emergency Contact (optional)</Label>
                        <Input
                            id="emergency_contact"
                            value={data.emergency_contact}
                            onChange={(e) => setData('emergency_contact', e.target.value)}
                            className="bg-white"
                        />
                        <InputError message={errors.emergency_contact} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Label htmlFor="is_active" className="cursor-pointer">Active</Label>
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500"
                        />
                    </div>

                    <div className="flex items-center gap-3 pt-4">
                        <Button type="submit" disabled={processing} className="bg-orange-500 hover:bg-orange-600 text-white">
                            Update Tenant
                        </Button>
                        <Link href="/admin/tenants">
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
