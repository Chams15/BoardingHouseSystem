import { Head, router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Verification settings',
        href: '/settings/verification',
    },
];

export default function Verification() {
    const { auth, flash, errors: pageErrors } = usePage().props as {
        auth: { user: User };
        flash?: { success?: string; error?: string };
        errors?: Record<string, string | undefined>;
    };

    const [idDocument, setIdDocument] = useState<File | null>(null);
    const [verificationErrors, setVerificationErrors] = useState<Record<string, string>>({});
    const verification = auth.user.tenant_profile;
    const verificationStatus = verification?.verification_status ?? 'Not_Submitted';
    const verificationApproved = verificationStatus === 'Approved';
    const errorBag = { ...pageErrors, ...verificationErrors };

    function handleVerificationSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        setVerificationErrors({});

        router.post(
            '/verification',
            {
                id_document: idDocument,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => setIdDocument(null),
                onError: (errors) => setVerificationErrors(errors as Record<string, string>),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verification settings" />

            <h1 className="sr-only">Verification settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Tenant Verification"
                        description={verificationApproved ? 'Your verification is approved. Additional submissions are disabled.' : 'Upload a valid government ID for admin review.'}
                    />

                    {flash?.success && <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{flash.success}</div>}
                    {flash?.error && <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

                    <div className="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 space-y-3">
                        <div className="grid gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <p><strong>Status:</strong> {verificationStatus}</p>
                            <p><strong>Submitted:</strong> {verification?.verification_submitted_at ? new Date(verification.verification_submitted_at).toLocaleString() : '—'}</p>
                            <p><strong>Verified:</strong> {verification?.verified_at ? new Date(verification.verified_at).toLocaleString() : '—'}</p>
                            {verification?.verification_note && <p><strong>Admin Note:</strong> {verification.verification_note}</p>}
                            {verification?.id_doc_url && (
                                <p>
                                    <strong>Uploaded ID:</strong>{' '}
                                    <a href={verification.id_doc_url} target="_blank" rel="noreferrer" className="text-blue-600 underline">
                                        View current file
                                    </a>
                                </p>
                            )}
                        </div>

                        <form onSubmit={handleVerificationSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="id_document">Government ID</Label>
                                <Input
                                    id="id_document"
                                    type="file"
                                    accept="image/png,image/jpeg,.jpg,.jpeg,.png,.pdf,application/pdf"
                                    onChange={(e) => setIdDocument(e.target.files?.[0] ?? null)}
                                    disabled={verificationApproved}
                                />
                                <p className="mt-1 text-xs text-gray-500">Allowed: JPG, JPEG, PNG, PDF. Max size: 10MB.</p>
                                {errorBag.id_document && <p className="mt-1 text-xs text-red-600">{errorBag.id_document}</p>}
                            </div>

                            {!verificationApproved ? (
                                <Button type="submit" className="bg-orange-500 text-white hover:bg-orange-600" disabled={!idDocument}>
                                    {verificationStatus === 'Pending' ? 'Update Verification Request' : verificationStatus === 'Rejected' ? 'Resubmit Verification' : 'Submit Verification'}
                                </Button>
                            ) : (
                                <Button type="button" disabled className="bg-gray-400 text-white">
                                    Verified
                                </Button>
                            )}
                        </form>
                    </div>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
