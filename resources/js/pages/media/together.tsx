import { Head, Link, router } from '@inertiajs/react';

import MediaPoster from '@/components/media/media-poster';
import { cardGrid, EmptyState } from '@/components/page-header';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { contrastTextColor } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { MediaItemSummary, TogetherMember } from '@/types/media';

interface TogetherProps {
    members: TogetherMember[];
    selectedMemberId: number | null;
    items: MediaItemSummary[];
}

export default function Together({ members, selectedMemberId, items }: TogetherProps) {
    function selectMember(id: number) {
        router.get(route('media.together'), { member: id }, { preserveState: true, preserveScroll: true });
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.together')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('media.together')}</h1>

            <div className="flex flex-wrap gap-2">
                {members.map((member) => {
                    const active = member.id === selectedMemberId;
                    const color = member.color ?? '#64748b';
                    return (
                        <button
                            key={member.id}
                            type="button"
                            onClick={() => selectMember(member.id)}
                            aria-pressed={active}
                            className={cn(
                                'bg-card flex h-11 items-center gap-2 rounded-full border-[1.5px] pr-4 pl-2 text-base font-medium transition-colors',
                                !active && 'hover:border-foreground/40',
                            )}
                            style={active ? { background: color, borderColor: color, color: contrastTextColor(color) } : undefined}
                        >
                            <span
                                className="size-7 rounded-full border-2"
                                style={{ background: color, borderColor: active ? 'currentColor' : color }}
                            />
                            {member.name}
                        </button>
                    );
                })}
            </div>

            {selectedMemberId === null && <EmptyState className="mt-6">{t('media.togetherPrompt')}</EmptyState>}

            {selectedMemberId !== null && items.length === 0 && <EmptyState className="mt-6">{t('media.togetherEmpty')}</EmptyState>}

            {items.length > 0 && (
                <div className={cn(cardGrid, 'mt-6')}>
                    {items.map((item) => (
                        <Link
                            key={item.id}
                            href={route('media.items.show', item.id)}
                            className="bg-card hover:bg-muted/60 flex items-center gap-3 rounded-2xl border-[1.5px] p-3 transition-colors"
                        >
                            <MediaPoster path={item.poster_path} alt={item.title_de} className="h-20 w-14" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">{item.title_de}</p>
                                {item.release_date && <p className="text-muted-foreground text-sm">{item.release_date.slice(0, 4)}</p>}
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </CheckstuLayout>
    );
}
