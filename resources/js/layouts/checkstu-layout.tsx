import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, House, ListChecks, LogOut, Users } from 'lucide-react';
import { PropsWithChildren } from 'react';

import PullToRefresh from '@/components/pull-to-refresh';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

interface NavItem {
    label: string;
    icon: typeof House;
    routeName: string;
    href: string;
}

const navItems: NavItem[] = [
    { label: t('nav.today'), icon: House, routeName: 'home', href: '/' },
    { label: t('nav.tasks'), icon: ListChecks, routeName: 'tasks.index', href: '/tasks' },
    { label: t('nav.upcoming'), icon: CalendarDays, routeName: 'upcoming', href: '/upcoming' },
    { label: t('nav.family'), icon: Users, routeName: 'family', href: '/family' },
];

export default function CheckstuLayout({ children }: PropsWithChildren) {
    const page = usePage<SharedData>();
    const current = page.url;
    const isGuest = page.props.auth.user.role === 'guest';

    // Guests only ever deal with their own tasks — no family/admin area.
    const items = navItems.filter((item) => item.routeName !== 'family' || !isGuest);

    return (
        <div className="bg-background text-foreground mx-auto flex min-h-screen w-full max-w-2xl flex-col">
            <header className="sticky top-0 z-10 flex items-center justify-between border-b bg-background/80 px-4 py-3 backdrop-blur">
                <span className="text-lg font-semibold tracking-tight">{t('common.appName')}</span>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm"
                >
                    <LogOut className="size-4" />
                    Abmelden
                </Link>
            </header>

            <main className="flex-1 px-4 pb-28 pt-4">
                <PullToRefresh>{children}</PullToRefresh>
            </main>

            {/* Extra 0.75rem on top of the safe-area inset: iOS's swipe-up-for-app-switcher
                gesture zone extends a bit beyond the inset value itself, so the inset alone
                isn't enough clearance for the nav to stay comfortably tappable. */}
            <nav className="fixed inset-x-0 bottom-0 z-10 mx-auto flex w-full max-w-2xl items-stretch border-t bg-background/95 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] backdrop-blur">
                {items.map((item) => {
                    const active = item.href === '/' ? current === '/' : current.startsWith(item.href);
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.routeName}
                            href={item.href}
                            className={cn(
                                'flex flex-1 flex-col items-center gap-1 py-2 text-xs',
                                active ? 'text-primary font-medium' : 'text-muted-foreground',
                            )}
                            aria-current={active ? 'page' : undefined}
                        >
                            <Icon className="size-5" />
                            {item.label}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}
