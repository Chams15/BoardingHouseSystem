import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Tenant = {
    user_id: number;
    email: string;
    tenant_profile?: {
        full_name: string;
        contact_number: string;
    };
};

type Room = {
    room_id: number;
    room_number: string;
    price_monthly: string;
};

type LeaseContract = {
    contract_id: number;
    tenant_id: number;
    room_id: number;
    start_date: string;
    end_date: string;
    security_deposit: string;
    contract_status: string;
};

type Props = {
    room?: Room;
    lease?: LeaseContract;
    tenants: Tenant[];
};

export default function LeaseForm({ room, lease, tenants }: Props) {
    const { props } = usePage();
    const isEdit = !!lease;

    const breadcrumbs = isEdit
        ? [
              { title: 'Admin', href: '/admin/dashboard' },
              { title: 'Rooms', href: '/admin/rooms' },
              { title: 'Edit Lease', href: '#' },
          ]
        : [
              { title: 'Admin', href: '/admin/dashboard' },
              { title: 'Rooms', href: '/admin/rooms' },
              { title: 'Create Lease', href: '#' },
          ];

    const { data, setData, post, put, processing, errors } = useForm({
        tenant_id: lease?.tenant_id ?? '',
        room_id: lease?.room_id ?? room?.room_id ?? '',
        start_date: lease?.start_date ?? '',
        end_date: lease?.end_date ?? '',
        security_deposit: lease?.security_deposit ?? '',
        contract_status: lease?.contract_status ?? 'Active',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            put(`/admin/leases/${lease!.contract_id}`, {
                onSuccess: () => {
                    // Success message will be shown via flash
                },
            });
        } else {
            post('/admin/leases', {
                onSuccess: () => {
                    // Success message will be shown via flash
                },
            });
        }
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? 'Edit Lease' : 'Create Lease'} />

            <div className="max-w-2xl">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {isEdit ? 'Edit Lease' : `Create Lease for Room ${room?.room_number}`}
                    </h1>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-6 rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 p-6 shadow-sm"
                >
                    {/* Tenant */}
                    <div>
                        <Label htmlFor="tenant_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tenant <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={String(data.tenant_id)}
                            onValueChange={(value) => setData('tenant_id', Number(value))}
                        >
                            <SelectTrigger className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100">
                                <SelectValue placeholder="Select a tenant" />
                            </SelectTrigger>
                            <SelectContent className="dark:bg-neutral-800 dark:border-neutral-700">
                                {tenants.map((tenant) => (
                                    <SelectItem key={tenant.user_id} value={String(tenant.user_id)}>
                                        {tenant.tenant_profile?.full_name || tenant.email}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.tenant_id && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.tenant_id}</p>
                        )}
                    </div>

                    {/* Start Date */}
                    <div>
                        <Label htmlFor="start_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Start Date <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)}
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.start_date && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.start_date}</p>
                        )}
                    </div>

                    {/* End Date */}
                    <div>
                        <Label htmlFor="end_date" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            End Date <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(e) => setData('end_date', e.target.value)}
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.end_date && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.end_date}</p>
                        )}
                    </div>

                    {/* Security Deposit */}
                    <div>
                        <Label htmlFor="security_deposit" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Security Deposit (₱)
                        </Label>
                        <Input
                            id="security_deposit"
                            type="number"
                            step="0.01"
                            value={data.security_deposit}
                            onChange={(e) => setData('security_deposit', e.target.value)}
                            placeholder="0.00"
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.security_deposit && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.security_deposit}</p>
                        )}
                    </div>

                    {/* Status (only on edit) */}
                    {isEdit && (
                        <div>
                            <Label htmlFor="contract_status" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status <span className="text-red-500">*</span>
                            </Label>
                            <Select
                                value={data.contract_status}
                                onValueChange={(value) => setData('contract_status', value)}
                            >
                                <SelectTrigger className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="dark:bg-neutral-800 dark:border-neutral-700">
                                    <SelectItem value="Active">Active</SelectItem>
                                    <SelectItem value="Pending_MoveOut">Pending Move-Out</SelectItem>
                                    <SelectItem value="Terminated">Terminated</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.contract_status && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.contract_status}</p>
                            )}
                        </div>
                    )}

                    {/* Submit Button */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            {processing ? 'Saving...' : isEdit ? 'Update Lease' : 'Create Lease'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.location.href = '/admin/rooms'}
                            className="dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
