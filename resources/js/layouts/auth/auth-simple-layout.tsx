import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="auth-canvas relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 p-6 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-900 md:p-10">
            <div className="auth-orb auth-orb-lg" aria-hidden="true" />
            <div className="auth-orb auth-orb-sm" aria-hidden="true" />
            <div className="relative z-10 w-full max-w-sm">
                <div className="auth-rise flex flex-col gap-8 rounded-2xl border border-white/65 bg-white/92 p-8 text-gray-900 shadow-2xl backdrop-blur-sm dark:border-neutral-800 dark:bg-neutral-900/90 dark:text-gray-100">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-orange-500 shadow-lg shadow-orange-500/35">
                                <AppLogoIcon className="size-7 fill-current text-white" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-semibold text-gray-900 dark:text-gray-100">{title}</h1>
                            <p className="text-center text-sm text-gray-500 dark:text-gray-400">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
