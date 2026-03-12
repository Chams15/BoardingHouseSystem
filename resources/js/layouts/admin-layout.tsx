import { Head, Link, usePage } from '@inertiajs/react';
import { LayoutGrid, DoorOpen, Users } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { Auth, BreadcrumbItem } from '@/types';
import type { PropsWithChildren } from 'react';

const navItems = [
    { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutGrid },
    { title: 'Tenants', href: '/admin/tenants', icon: Users },
    { title: 'Rooms', href: '/admin/rooms', icon: DoorOpen },
];

export default function AdminLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const getInitials = useInitials();
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <div className="min-h-svh bg-gray-50">
            <nav className="border-b bg-white shadow-sm">
                <div className="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                    <Link href="/admin/dashboard" className="flex items-center gap-2">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500">
                            <AppLogoIcon className="size-5 fill-current text-white" />
                        </div>
                        <span className="text-lg font-semibold text-gray-900">Admin Panel</span>
                    </Link>

                    <div className="ml-8 flex items-center gap-1">
                        {navItems.map((item) => (
                            <Link
                                key={item.title}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    isCurrentUrl(item.href)
                                        ? 'bg-orange-50 text-orange-700'
                                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                                )}
                            >
                                <item.icon className="h-4 w-4" />
                                {item.title}
                            </Link>
                        ))}
                    </div>

                    <div className="ml-auto">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="size-10 rounded-full p-1">
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage src={auth.user.avatar as string} alt={auth.user.email} />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black">
                                            {getInitials(auth.user.email)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </nav>

            {breadcrumbs.length > 0 && (
                <div className="border-b bg-white">
                    <div className="mx-auto flex h-10 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                        <nav className="flex gap-1 text-sm text-gray-500">
                            {breadcrumbs.map((crumb, i) => (
                                <span key={i} className="flex items-center gap-1">
                                    {i > 0 && <span>/</span>}
                                    {i === breadcrumbs.length - 1 ? (
                                        <span className="text-gray-900">{crumb.title}</span>
                                    ) : (
                                        <Link href={crumb.href} className="hover:text-gray-700">
                                            {crumb.title}
                                        </Link>
                                    )}
                                </span>
                            ))}
                        </nav>
                    </div>
                </div>
            )}

            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
