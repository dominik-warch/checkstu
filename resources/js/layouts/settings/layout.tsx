import { chipClass } from '@/components/chip-row';
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

            <nav className="-mx-4 mb-6 flex gap-1.5 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:px-0" aria-label={t('settings.menuTitle')}>
                {sidebarNavItems.map((item) => {
                    const active = currentPath === item.url;
                    return (
                        <Link
                            key={item.url}
                            href={item.url}
                            prefetch
                            aria-current={active ? 'page' : undefined}
                            // The menu opens on the last chip; bring the active one into view on narrow screens.
                            ref={active ? (el: HTMLAnchorElement | null) => el?.scrollIntoView({ inline: 'center', block: 'nearest' }) : undefined}
                            className={chipClass(active)}
                        >
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <div className="max-w-xl space-y-10">{children}</div>
        </CheckstuLayout>
    );
}
