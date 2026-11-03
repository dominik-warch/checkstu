import { Link, usePage } from '@inertiajs/react';
import { Archive, CalendarDays, Dices, Film, House, Library, ListChecks, LogOut, Menu, Settings, Sun, Trophy, Users } from 'lucide-react';
import { CSSProperties, PropsWithChildren, useEffect, useState } from 'react';

import PullToRefresh from '@/components/pull-to-refresh';
import { Sheet, SheetClose, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { contrastTextColor } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { previousPage } from '@/lib/previous-page';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

export interface NavItem {
    label: string;
    icon: typeof House;
    routeName: string;
    href: string;
}

export const tasksNavItems: NavItem[] = [
    { label: t('nav.today'), icon: Sun, routeName: 'home', href: '/' },
    { label: t('nav.tasks'), icon: ListChecks, routeName: 'tasks.index', href: '/tasks' },
];

export const mediaNavItems: NavItem[] = [
    { label: t('media.home'), icon: Film, routeName: 'media.home', href: '/media' },
    { label: t('media.comingUp'), icon: CalendarDays, routeName: 'media.comingUp', href: '/media/coming-up' },
    { label: t('media.library'), icon: Library, routeName: 'media.library', href: '/media/library' },
    { label: t('media.together'), icon: Users, routeName: 'media.together', href: '/media/together' },
];

export const boardGamesNavItems: NavItem[] = [
    { label: t('boardGames.catalogue'), icon: Dices, routeName: 'boardgames.catalogue', href: '/boardgames' },
    { label: t('boardGames.plays'), icon: Trophy, routeName: 'boardgames.plays.index', href: '/boardgames/plays' },
];

type Context = 'tasks' | 'media' | 'boardgames';

const contexts: { id: Context; href: string; icon: typeof House; label: string }[] = [
    { id: 'tasks', href: '/', icon: House, label: t('nav.switchToTasks') },
    { id: 'media', href: '/media', icon: Film, label: t('nav.switchToMedia') },
    { id: 'boardgames', href: '/boardgames', icon: Dices, label: t('nav.switchToBoardGames') },
];

// Users without a colour still play a colour: the board's indigo.
const FALLBACK_PLAYER = '#4f46e5';

/** The logged-in player's colour plus its computed text colour, as CSS variables. */
export function playerStyle(color: string | null | undefined): CSSProperties {
    const player = color || FALLBACK_PLAYER;
    return { '--player': player, '--player-ink': contrastTextColor(player) } as CSSProperties;
}

interface CheckstuLayoutProps {
    navItems?: NavItem[];
    context?: Context;
    /** Leave the middle of the phone tab bar free for the page's docked "+" (Heute, Aufgaben). */
    dockedAdd?: boolean;
}

export default function CheckstuLayout({
    children,
    navItems = tasksNavItems,
    context = 'tasks',
    dockedAdd = false,
}: PropsWithChildren<CheckstuLayoutProps>) {
    const page = usePage<SharedData>();
    const current = page.url;
    const user = page.props.auth.user;
    const isGuest = user.role === 'guest';
    const player = user.color || FALLBACK_PLAYER;

    // Below lg the add button steps aside while you scroll down through a list (it would
    // cover the row actions) and returns the moment you scroll back up.
    const [scrollingDown, setScrollingDown] = useState(false);
    useEffect(() => {
        let lastY = window.scrollY;
        const onScroll = () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) < 8) return;
            setScrollingDown(y > lastY && y > 80);
            lastY = y;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    // The installed PWA's status bar takes the player's colour too.
    useEffect(() => {
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', player);
    }, [player]);

    // A task's page belongs to the tab you opened it from, so the tab bar and its back link agree.
    const from = previousPage();
    const here = /^\/tasks\/\d+/.test(current) && from !== null && /^\/(\?|$)/.test(from) ? '/' : current;

    const isActive = (item: NavItem) => {
        // Section roots are exact-match only, otherwise they'd light up on every sub-page.
        const isSectionRoot = item.href === '/' || item.href === '/media' || item.href === '/boardgames';
        return isSectionRoot ? here === item.href || here.startsWith(item.href + '?') : here.startsWith(item.href);
    };

    const secondaryLinks = (
        <>
            {context === 'tasks' && <MenuLink href="/archive" icon={Archive} label={t('nav.archive')} active={current.startsWith('/archive')} />}
            {!isGuest && context === 'tasks' && (
                <MenuLink href="/family" icon={Users} label={t('nav.family')} active={current.startsWith('/family')} />
            )}
            <MenuLink href="/settings/notifications" icon={Settings} label={t('nav.settings')} active={current.startsWith('/settings')} />
            <Link
                href={route('logout')}
                method="post"
                as="button"
                className="flex min-h-11 items-center gap-3 rounded-full px-3 text-left text-sm font-medium hover:ring-[1.5px] hover:ring-current hover:ring-inset"
            >
                <LogOut className="size-5" />
                {t('settings.logOut')}
            </Link>
        </>
    );

    return (
        <div
            style={playerStyle(user.color as string | null)}
            data-scroll={scrollingDown ? 'down' : 'up'}
            className="group/shell bg-background text-foreground min-h-dvh lg:flex"
        >
            {/* Rail: tablet landscape and up. The whole rail is the player's colour. */}
            <aside className="bg-player text-player-ink sticky top-0 hidden h-dvh w-64 shrink-0 flex-col gap-6 overflow-y-auto px-4 py-6 [--color-ring:var(--player-ink)] [--ring:var(--player-ink)] lg:flex">
                <Link href="/" className="px-3 text-xl font-bold tracking-tight">
                    {t('common.appName')}
                </Link>

                <ContextSwitch context={context} withLabels />

                <nav className="flex flex-col gap-1" aria-label={t('nav.menu')}>
                    {navItems.map((item) => {
                        const active = isActive(item);
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.routeName}
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex min-h-11 items-center gap-3 rounded-full px-3 text-base font-medium transition-colors',
                                    active ? 'bg-player-ink text-player' : 'hover:ring-[1.5px] hover:ring-current hover:ring-inset',
                                )}
                            >
                                <Icon className="size-5" />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="mt-auto flex flex-col gap-1 border-t border-current/20 pt-4">{secondaryLinks}</div>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                {/* Phone/tablet header: a slim band in the player's colour. */}
                <header className="bg-player text-player-ink sticky top-0 z-20 pt-[env(safe-area-inset-top)] [--color-ring:var(--player-ink)] [--ring:var(--player-ink)] lg:hidden">
                    <div className="mx-auto flex h-14 w-full max-w-3xl items-center justify-between gap-3 px-4 md:max-w-5xl">
                        <Link href="/" className="text-lg font-bold tracking-tight">
                            {t('common.appName')}
                        </Link>
                        <div className="flex items-center gap-1.5">
                            <ContextSwitch context={context} />
                            <Sheet>
                                <SheetTrigger asChild>
                                    <button
                                        type="button"
                                        className="grid size-11 place-items-center rounded-full hover:ring-[1.5px] hover:ring-current hover:ring-inset"
                                        aria-label={t('nav.menu')}
                                    >
                                        <Menu className="size-5" />
                                    </button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    style={playerStyle(user.color as string | null)}
                                    className="bg-player text-player-ink flex w-72 flex-col gap-1 border-0 p-4 pt-[calc(env(safe-area-inset-top)+1rem)]"
                                >
                                    <SheetTitle className="text-player-ink mb-2 px-3 text-base font-bold">{user.name}</SheetTitle>
                                    <SheetClose asChild>
                                        <div className="flex flex-col gap-1">{secondaryLinks}</div>
                                    </SheetClose>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-3xl flex-1 px-4 pt-4 pb-[calc(env(safe-area-inset-bottom)+9.5rem)] md:max-w-5xl lg:max-w-6xl lg:px-10 lg:pt-10 lg:pb-16">
                    <PullToRefresh>{children}</PullToRefresh>
                </main>

                {/* Bottom tabs on phone and tablet portrait. The extra 0.75rem clears iOS's
                    home-indicator gesture zone, which reaches past the safe-area inset. */}
                <nav className="bg-card fixed inset-x-0 bottom-0 z-20 border-t pb-[calc(env(safe-area-inset-bottom)+0.5rem)] lg:hidden">
                    <div className="mx-auto flex w-full max-w-md items-stretch">
                        {navItems.map((item, index) => {
                            const active = isActive(item);
                            const Icon = item.icon;
                            return [
                                // The docked "+" sits in this gap, between the two Haushalt tabs.
                                dockedAdd && index === Math.ceil(navItems.length / 2) && (
                                    <span key="docked-add" className="w-20 shrink-0" aria-hidden />
                                ),
                                <Link
                                    key={item.routeName}
                                    href={item.href}
                                    className={cn(
                                        'flex flex-1 flex-col items-center gap-1 pt-2 pb-1 text-xs',
                                        active ? 'text-foreground font-bold' : 'text-muted-foreground font-medium',
                                    )}
                                    aria-current={active ? 'page' : undefined}
                                >
                                    <span
                                        className={cn(
                                            'grid h-8 w-14 place-items-center rounded-full transition-colors duration-300',
                                            active && 'bg-player text-player-ink',
                                        )}
                                    >
                                        <Icon className="size-5" />
                                    </span>
                                    {item.label}
                                </Link>,
                            ];
                        })}
                    </div>
                </nav>
            </div>
        </div>
    );
}

/** Round context buttons: tasks, media, board games. The active one is a filled field. */
function ContextSwitch({ context, withLabels = false }: { context: Context; withLabels?: boolean }) {
    return (
        <div className={cn('flex', withLabels ? 'flex-col gap-1' : 'gap-1')}>
            {contexts.map((c) => {
                const active = c.id === context;
                const Icon = c.icon;
                return (
                    <Link
                        key={c.id}
                        href={c.href}
                        aria-label={withLabels ? undefined : c.label}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                            'flex items-center gap-3 rounded-full transition-colors',
                            withLabels ? 'min-h-11 px-3 text-sm font-medium' : 'size-11 justify-center',
                            active ? withLabels && 'font-bold' : 'hover:ring-[1.5px] hover:ring-current hover:ring-inset',
                        )}
                    >
                        <span
                            className={cn(
                                'grid size-8 shrink-0 place-items-center rounded-full border-2 border-current',
                                active && 'bg-player-ink text-player border-transparent',
                            )}
                        >
                            <Icon className="size-4" />
                        </span>
                        {withLabels && c.label}
                    </Link>
                );
            })}
        </div>
    );
}

function MenuLink({ href, icon: Icon, label, active }: { href: string; icon: typeof House; label: string; active: boolean }) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex min-h-11 items-center gap-3 rounded-full px-3 text-sm font-medium',
                active ? 'bg-player-ink text-player' : 'hover:ring-[1.5px] hover:ring-current hover:ring-inset',
            )}
        >
            <Icon className="size-5" />
            {label}
        </Link>
    );
}
