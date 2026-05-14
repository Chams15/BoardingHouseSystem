import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AdminLayout from '@/layouts/admin-layout';
import { useState } from 'react';

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Create', href: '/admin/tenants/create' },
];

const requiredFields = [
    'full_name',
    'email',
    'contact_number',
    'contact_address',
    'password',
    'password_confirmation',
];

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function CreateTenant() {
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

    const validateForm = (formData: FormData): boolean => {
        const errors: Record<string, string> = {};

        requiredFields.forEach((field) => {
            const value = formData.get(field);
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                const fieldLabel = field
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, (l: string) => l.toUpperCase());
                errors[field] = `The ${fieldLabel} field is required`;
            }
        });

        const email = formData.get('email');
        if (typeof email === 'string' && email.trim() !== '' && !emailPattern.test(email.trim())) {
            errors.email = 'The email must be a valid email address.';
        }

        setValidationErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        const form = e.currentTarget;
        const formData = new FormData(form);
        
        if (!validateForm(formData)) {
            e.preventDefault();
        }
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Tenant" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Tenant</h1>

                <Form
                    action="/admin/tenants"
                    method="post"
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    noValidate
                    className="space-y-4 rounded-xl border bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                    onSubmit={handleSubmit}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="full_name" className="text-gray-700 dark:text-gray-300">Full Name</Label>
                                <Input
                                    id="full_name"
                                    type="text"
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="full_name"
                                    placeholder="Full name"
                                    className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                                />
                                <InputError message={errors.full_name || validationErrors.full_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email" className="text-gray-700 dark:text-gray-300">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                    className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                                />
                                <InputError message={errors.email || validationErrors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contact_number" className="text-gray-700 dark:text-gray-300">Contact Number</Label>
                                <Input
                                    id="contact_number"
                                    type="tel"
                                    tabIndex={3}
                                    autoComplete="tel"
                                    name="contact_number"
                                    placeholder="09XX XXX XXXX"
                                    className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                                />
                                <InputError message={errors.contact_number || validationErrors.contact_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contact_address" className="text-gray-700 dark:text-gray-300">Contact Address</Label>
                                <Input
                                    id="contact_address"
                                    type="text"
                                    tabIndex={4}
                                    autoComplete="street-address"
                                    name="contact_address"
                                    placeholder="House no., street, barangay, city"
                                    className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                                />
                                <InputError message={errors.contact_address || validationErrors.contact_address} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="emergency_contact" className="text-gray-700 dark:text-gray-300">Emergency Contact (optional)</Label>
                                <Input
                                    id="emergency_contact"
                                    type="text"
                                    tabIndex={5}
                                    name="emergency_contact"
                                    placeholder="Emergency contact number"
                                    className="bg-white text-gray-900 dark:bg-neutral-950 dark:text-gray-100 dark:border-neutral-700"
                                />
                                <InputError message={errors.emergency_contact} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password" className="text-gray-700 dark:text-gray-300">Password</Label>
                                <PasswordInput
                                    id="password"
                                    tabIndex={6}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password || validationErrors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation" className="text-gray-700 dark:text-gray-300">Confirm password</Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    tabIndex={7}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                />
                                <InputError message={errors.password_confirmation || validationErrors.password_confirmation} />
                            </div>

                            <div className="flex items-center gap-3 pt-4">
                                <Button type="submit" disabled={processing} className="bg-orange-500 hover:bg-orange-600 text-white">
                                    {processing && <Spinner />}
                                    Create Tenant
                                </Button>
                                <Link href="/admin/tenants">
                                    <Button type="button" variant="outline">Cancel</Button>
                                </Link>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AdminLayout>
    );
}
