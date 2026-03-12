import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'text-orange-600 underline decoration-orange-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-orange-600 dark:text-orange-400 dark:decoration-orange-500',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
