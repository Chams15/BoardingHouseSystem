import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { LogOut } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';

type VisitorLog = {
    log_id: number;
    visitor_name: string;
    visitor_photo_url: string | null;
    visitor_photo_path?: string | null;
    purpose: string | null;
    time_in: string;
    time_out: string | null;
};

type Props = {
    visitorLogs: VisitorLog[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Visitors', href: '/visitors' },
];

export default function VisitorsIndex({ visitorLogs }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const errors = (props.errors ?? {}) as Record<string, string | undefined>;

    const [visitorName, setVisitorName] = useState('');
    const [purpose, setPurpose] = useState('');
    const [visitorPhoto, setVisitorPhoto] = useState<File | null>(null);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        router.post(
            '/visitors',
            {
                visitor_name: visitorName,
                purpose,
                visitor_photo: visitorPhoto,
            },
            {
                forceFormData: true,
                onSuccess: () => {
                    setVisitorName('');
                    setPurpose('');
                    setVisitorPhoto(null);
                },
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Visitor Registration" />

            <div className="space-y-6 p-4">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Visitor Registration</h1>

                {flash?.success && <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                {flash?.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

                <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div>
                        <label htmlFor="visitor_name" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Visitor Name
                        </label>
                        <input
                            id="visitor_name"
                            value={visitorName}
                            onChange={(e) => setVisitorName(e.target.value)}
                            required
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                        {errors.visitor_name && <p className="mt-1 text-xs text-red-600">{errors.visitor_name}</p>}
                    </div>

                    <div>
                        <label htmlFor="purpose" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Purpose (optional)
                        </label>
                        <input
                            id="purpose"
                            value={purpose}
                            onChange={(e) => setPurpose(e.target.value)}
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                        {errors.purpose && <p className="mt-1 text-xs text-red-600">{errors.purpose}</p>}
                    </div>

                    <div>
                        <label htmlFor="visitor_photo" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Visitor Photo (optional)
                        </label>
                        <input
                            id="visitor_photo"
                            type="file"
                            accept="image/png,image/jpeg,.jpg,.jpeg,.png"
                            onChange={(e) => setVisitorPhoto(e.target.files?.[0] ?? null)}
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 file:mr-4 file:rounded-md file:border-0 file:bg-orange-100 file:px-3 file:py-1 file:text-orange-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                        />
                        <p className="mt-1 text-xs text-gray-500">Allowed: JPG, JPEG, PNG. Max size: 10MB.</p>
                        {errors.visitor_photo && <p className="mt-1 text-xs text-red-600">{errors.visitor_photo}</p>}
                    </div>

                    <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600">
                        Register Visitor
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Photo</th>
                                <th className="px-4 py-3 font-medium">Visitor</th>
                                <th className="px-4 py-3 font-medium">Purpose</th>
                                <th className="px-4 py-3 font-medium">Time In</th>
                                <th className="px-4 py-3 font-medium">Time Out</th>
                                <th className="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {visitorLogs.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No visitors registered yet.</td>
                                </tr>
                            ) : (
                                visitorLogs.map((log) => (
                                    <tr key={log.log_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3">
                                            {log.visitor_photo_url ? (
                                                <a href={log.visitor_photo_url} target="_blank" rel="noreferrer" title="Open visitor photo">
                                                    <img
                                                        src={log.visitor_photo_url}
                                                        alt={log.visitor_name}
                                                        className="h-10 w-10 rounded-md object-cover transition-transform hover:scale-105"
                                                    />
                                                </a>
                                            ) : (
                                                <div className="h-10 w-10 rounded-md bg-gray-100 dark:bg-neutral-800" />
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-200">{log.visitor_name}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.purpose ?? '—'}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{new Date(log.time_in).toLocaleString()}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{log.time_out ? new Date(log.time_out).toLocaleString() : '—'}</td>
                                        <td className="px-4 py-3 text-right">
                                            {!log.time_out && (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                                    onClick={() => {
                                                        if (confirm('Check out this visitor?')) {
                                                            router.post(`/visitors/${log.log_id}/checkout`);
                                                        }
                                                    }}
                                                >
                                                    <LogOut className="h-4 w-4 mr-1" />
                                                    Checkout
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
