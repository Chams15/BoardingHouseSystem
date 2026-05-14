import { Button } from '@/components/ui/button';

type Props = {
    current: 'visitors' | 'incidents' | 'blacklist';
};

const sections = [
    { key: 'visitors', label: 'Visitors', href: '/admin/security/visitors' },
    { key: 'incidents', label: 'Security Incidents', href: '/admin/security/incidents' },
    { key: 'blacklist', label: 'Blacklist', href: '/admin/security/blacklist' },
] as const;

export default function SecurityNav({ current }: Props) {
    return (
        <div className="flex flex-wrap gap-2 rounded-xl border bg-white p-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            {sections.map((section) => (
                <Button key={section.key} asChild variant={current === section.key ? 'default' : 'outline'} className={current === section.key ? 'bg-orange-500 hover:bg-orange-600' : ''}>
                    <a href={section.href}>{section.label}</a>
                </Button>
            ))}
        </div>
    );
}
