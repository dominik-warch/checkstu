import { Head, Link, router } from '@inertiajs/react';

import MediaPoster from '@/components/media/media-poster';
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
                            className={cn('rounded-full border px-3 py-1.5 text-sm font-medium transition-colors', !active && 'hover:bg-accent')}
                            style={active ? { background: color, borderColor: color, color: contrastTextColor(color) } : undefined}
                        >
                            {member.name}
                        </button>
                    );
                })}
            </div>

            {selectedMemberId === null && (
                <div className="text-muted-foreground mt-8 rounded-xl border border-dashed p-8 text-center">{t('media.togetherPrompt')}</div>
            )}

            {selectedMemberId !== null && items.length === 0 && (
                <div className="text-muted-foreground mt-8 rounded-xl border border-dashed p-8 text-center">{t('media.togetherEmpty')}</div>
            )}

            {items.length > 0 && (
                <div className="mt-6 flex flex-col gap-2">
                    {items.map((item) => (
                        <Link
                            key={item.id}
                            href={route('media.items.show', item.id)}
                            className="hover:bg-muted/50 flex items-center gap-3 rounded-xl border p-3"
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
