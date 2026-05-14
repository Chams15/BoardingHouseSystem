import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import type { User } from '@/types';
import { useState } from 'react';

type Props = {
    tenant: User;
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Edit', href: '#' },
];

const requiredFields = [
    'full_name',
    'email',
    'contact_number',
    'contact_address',
];

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function EditTenant({ tenant }: Props) {
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
    const { data, setData, put, processing, errors } = useForm({
        full_name: tenant.tenant_profile?.full_name ?? '',
        email: tenant.email,
        contact_number: tenant.tenant_profile?.contact_number ?? '',
        contact_address: tenant.tenant_profile?.contact_address ?? '',
        emergency_contact: tenant.tenant_profile?.emergency_contact ?? '',
        is_active: tenant.is_active,
    });

    const validateForm = (): boolean => {
        const newErrors: Record<string, string> = {};

        requiredFields.forEach((field) => {
            const value = data[field as keyof typeof data];
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                const fieldLabel = field
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, (l: string) => l.toUpperCase());
                newErrors[field] = `The ${fieldLabel} field is required`;
            }
        });

        const email = data.email;
        if (typeof email === 'string' && email.trim() !== '' && !emailPattern.test(email.trim())) {
            newErrors.email = 'The email must be a valid email address.';
        }

        setValidationErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (validateForm()) {
            put(`/admin/tenants/${tenant.user_id}`);
        }
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Tenant" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Tenant</h1>

                <form onSubmit={handleSubmit} noValidate className="space-y-4 rounded-xl border bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="grid gap-2">
                        <Label htmlFor="full_name" className="text-gray-700 dark:text-gray-300">Full Name</Label>
                        <Input
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.full_name || validationErrors.full_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email" className="text-gray-700 dark:text-gray-300">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.email || validationErrors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_number" className="text-gray-700 dark:text-gray-300">Contact Number</Label>
                        <Input
                            id="contact_number"
                            type="tel"
                            value={data.contact_number}
                            onChange={(e) => setData('contact_number', e.target.value)}
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.contact_number || validationErrors.contact_number} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_address" className="text-gray-700 dark:text-gray-300">Contact Address</Label>
                        <Input
                            id="contact_address"
                            value={data.contact_address}
                            onChange={(e) => setData('contact_address', e.target.value)}
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.contact_address || validationErrors.contact_address} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="emergency_contact" className="text-gray-700 dark:text-gray-300">Emergency Contact (optional)</Label>
                        <Input
                            id="emergency_contact"
                            value={data.emergency_contact}
                            onChange={(e) => setData('emergency_contact', e.target.value)}
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.emergency_contact} />
                    </div>

                    <div className="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <Label htmlFor="is_active" className="cursor-pointer text-gray-700 dark:text-gray-300">Active</Label>
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 bg-white text-orange-500 focus:ring-orange-500 dark:border-neutral-600 dark:bg-neutral-950"
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
