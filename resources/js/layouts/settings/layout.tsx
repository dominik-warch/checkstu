import { Button } from '@/components/ui/button';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';

const sidebarNavItems: NavItem[] = [
    {
        title: t('settings.profileTitle'),
        url: '/settings/profile',
        icon: null,
    },
    {
        title: t('settings.passwordTitle'),
        url: '/settings/password',
        icon: null,
    },
    {
        title: t('settings.appearanceTitle'),
        url: '/settings/appearance',
        icon: null,
    },
    {
        title: t('notifications.title'),
        url: '/settings/notifications',
        icon: null,
    },
];

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const currentPath = window.location.pathname;

    return (
        <CheckstuLayout>
            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('settings.menuTitle')}</h1>

            <nav className="mb-6 flex flex-wrap gap-2">
                {sidebarNavItems.map((item) => (
                    <Button key={item.url} size="sm" variant={currentPath === item.url ? 'default' : 'outline'} asChild>
                        <Link href={item.url} prefetch>
                            {item.title}
                        </Link>
                    </Button>
                ))}
            </nav>

            <div className="space-y-12">{children}</div>
        </CheckstuLayout>
    );
}
