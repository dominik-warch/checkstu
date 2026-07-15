import { Link, usePage } from '@inertiajs/react';
import { Archive, CalendarDays, Film, House, Library, ListChecks, LogOut, Menu, Settings, Users } from 'lucide-react';
import { PropsWithChildren } from 'react';

import ContextSwitcher from '@/components/context-switcher';
import PullToRefresh from '@/components/pull-to-refresh';
import { Sheet, SheetClose, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

export interface NavItem {
    label: string;
    icon: typeof House;
    routeName: string;
    href: string;
}

export const tasksNavItems: NavItem[] = [
    { label: t('nav.today'), icon: House, routeName: 'home', href: '/' },
    { label: t('nav.tasks'), icon: ListChecks, routeName: 'tasks.index', href: '/tasks' },
    { label: t('nav.upcoming'), icon: CalendarDays, routeName: 'upcoming', href: '/upcoming' },
];

export const mediaNavItems: NavItem[] = [
    { label: t('media.home'), icon: Film, routeName: 'media.home', href: '/media' },
    { label: t('media.comingUp'), icon: CalendarDays, routeName: 'media.comingUp', href: '/media/coming-up' },
    { label: t('media.library'), icon: Library, routeName: 'media.library', href: '/media/library' },
];

interface CheckstuLayoutProps {
    navItems?: NavItem[];
    context?: 'tasks' | 'media';
}

export default function CheckstuLayout({ children, navItems = tasksNavItems, context = 'tasks' }: PropsWithChildren<CheckstuLayoutProps>) {
    const page = usePage<SharedData>();
    const current = page.url;
    const isGuest = page.props.auth.user.role === 'guest';

    return (
        <div className="bg-background text-foreground mx-auto flex min-h-screen w-full max-w-2xl flex-col">
            <header className="bg-background/80 sticky top-0 z-10 flex items-center justify-between border-b px-4 py-3 backdrop-blur">
                <ContextSwitcher context={context} />
                <div className="flex items-center gap-3">
                    {context === 'tasks' && (
                        <Link
                            href="/archive"
                            className={cn(
                                'inline-flex items-center gap-1 text-sm',
                                current.startsWith('/archive') ? 'text-primary font-medium' : 'text-muted-foreground hover:text-foreground',
                            )}
                            aria-label={t('nav.archive')}
                        >
                            <Archive className="size-4" />
                        </Link>
                    )}
                    <Sheet>
                        <SheetTrigger asChild>
                            <button
                                type="button"
                                className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm"
                                aria-label={t('nav.menu')}
                            >
                                <Menu className="size-4" />
                            </button>
                        </SheetTrigger>
                        <SheetContent side="right" className="flex w-64 flex-col gap-1 p-4">
                            <SheetTitle className="sr-only">{t('nav.menu')}</SheetTitle>

                            {!isGuest && context === 'tasks' && (
                                <SheetClose asChild>
                                    <Link href="/family" className="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-sm">
                                        <Users className="size-4" />
                                        {t('nav.family')}
                                    </Link>
                                </SheetClose>
                            )}

                            <SheetClose asChild>
                                <Link href="/settings/notifications" className="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-sm">
                                    <Settings className="size-4" />
                                    {t('nav.settings')}
                                </Link>
                            </SheetClose>

                            <SheetClose asChild>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-left text-sm"
                                >
                                    <LogOut className="size-4" />
                                    {t('settings.logOut')}
                                </Link>
                            </SheetClose>
                        </SheetContent>
                    </Sheet>
                </div>
            </header>

            <main className="flex-1 px-4 pt-4 pb-28">
                <PullToRefresh>{children}</PullToRefresh>
            </main>

            {/* Extra 0.75rem on top of the safe-area inset: iOS's swipe-up-for-app-switcher
                gesture zone extends a bit beyond the inset value itself, so the inset alone
                isn't enough clearance for the nav to stay comfortably tappable. */}
            <nav className="bg-background/95 fixed inset-x-0 bottom-0 z-10 mx-auto flex w-full max-w-2xl items-stretch border-t pb-[calc(env(safe-area-inset-bottom)+0.75rem)] backdrop-blur">
                {navItems.map((item) => {
                    // Section-root hrefs ('/', '/media') are exact-match only — otherwise they'd
                    // also light up as "active" on every sub-page whose href they're a prefix of.
                    const isSectionRoot = item.href === '/' || item.href === '/media';
                    const active = isSectionRoot ? current === item.href : current.startsWith(item.href);
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
