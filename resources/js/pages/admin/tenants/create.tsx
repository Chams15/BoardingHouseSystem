import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Create', href: '/admin/tenants/create' },
];

export default function CreateTenant() {
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        email: '',
        contact_number: '',
        emergency_contact: '',
        password: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/admin/tenants');
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Tenant" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Tenant</h1>

                <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="grid gap-2">
                        <Label htmlFor="full_name" className="text-gray-700 dark:text-gray-300">Full Name</Label>
                        <Input
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            required
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.full_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email" className="text-gray-700 dark:text-gray-300">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="contact_number" className="text-gray-700 dark:text-gray-300">Contact Number</Label>
                        <Input
                            id="contact_number"
                            type="tel"
                            value={data.contact_number}
                            onChange={(e) => setData('contact_number', e.target.value)}
                            required
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.contact_number} />
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

                    <div className="grid gap-2">
                        <Label htmlFor="password" className="text-gray-700 dark:text-gray-300">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center gap-3 pt-4">
                        <Button type="submit" disabled={processing} className="bg-orange-500 hover:bg-orange-600 text-white">
                            Create Tenant
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
