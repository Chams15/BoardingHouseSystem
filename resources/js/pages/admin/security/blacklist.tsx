import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import SecurityNav from './security-nav';

type Blacklist = {
    blacklist_id: number;
    email: string;
    reason: string;
    banned_at: string;
};

type Props = {
    blacklist: Blacklist[];
    filters: { search: string };
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Security', href: '/admin/security/blacklist' },
    { title: 'Blacklist', href: '/admin/security/blacklist' },
];

export default function AdminSecurityBlacklist({ blacklist, filters }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [search, setSearch] = useState(filters.search ?? '');
    const [blacklistEmail, setBlacklistEmail] = useState('');
    const [blacklistReason, setBlacklistReason] = useState('');

    function refreshResults(nextSearch = search) {
        router.get(
            '/admin/security/blacklist',
            { search: nextSearch },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function submitBlacklist(e: React.FormEvent) {
        e.preventDefault();
        router.post(
            '/admin/security/blacklist',
            { email: blacklistEmail, reason: blacklistReason },
            {
                onSuccess: () => {
                    setBlacklistEmail('');
                    setBlacklistReason('');
                },
            },
        );
    }

    function removeBlacklist(blacklistId: number) {
        if (confirm('Remove this email from blacklist?')) {
            router.delete(`/admin/security/blacklist/${blacklistId}`);
        }
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Security - Blacklist" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Security - Blacklist</h1>
                <SecurityNav current="blacklist" />

                <div className="grid gap-3 rounded-xl border bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            value={search}
                            onChange={(e) => {
                                const next = e.target.value;
                                setSearch(next);
                                refreshResults(next);
                            }}
                            placeholder="Search blacklist by email or reason"
                            className="pl-9"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            setSearch('');
                            refreshResults('');
                        }}
                    >
                        Clear filters
                    </Button>
                </div>

                {flash?.success && <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                {flash?.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Add to Blacklist</h2>

                        <form onSubmit={submitBlacklist} className="space-y-3">
                            <input
                                type="email"
                                value={blacklistEmail}
                                onChange={(e) => setBlacklistEmail(e.target.value)}
                                required
                                placeholder="Email to ban"
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            />
                            <textarea
                                value={blacklistReason}
                                onChange={(e) => setBlacklistReason(e.target.value)}
                                required
                                rows={3}
                                placeholder="Reason for ban"
                                className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100"
                            />
                            <Button type="submit" className="bg-red-600 text-white hover:bg-red-700">Add to Blacklist</Button>
                        </form>
                    </div>

                    <div className="space-y-2 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Blacklisted Emails</h2>

                        {blacklist.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">No blacklisted emails.</p>
                        ) : (
                            blacklist.map((entry) => (
                                <div key={entry.blacklist_id} className="rounded-lg border p-3 dark:border-neutral-700">
                                    <p className="text-sm font-medium text-gray-800 dark:text-gray-200">{entry.email}</p>
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{entry.reason}</p>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="mt-2 px-0 text-red-600 hover:text-red-800"
                                        onClick={() => removeBlacklist(entry.blacklist_id)}
                                    >
                                        Remove
                                    </Button>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
