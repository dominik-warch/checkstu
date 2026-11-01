import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import MemberDialog from '@/components/family/member-dialog';
import { cardGrid } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { contrastTextColor } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { FamilyMember } from '@/types/checkstu';

interface FamilyProps {
    members: FamilyMember[];
    can: { manageUsers: boolean };
}

export default function Family({ members, can }: FamilyProps) {
    const remove = (member: FamilyMember) => {
        if (confirm(t('family.deleteConfirm'))) {
            router.delete(route('users.destroy', member.id), { preserveScroll: true });
        }
    };

    return (
        <CheckstuLayout>
            <Head title={t('family.title')} />

            <div className="mb-4 flex items-center justify-between gap-4">
                <h1 className="text-2xl font-bold tracking-tight">{t('family.title')}</h1>
                {can.manageUsers && (
                    <MemberDialog
                        trigger={
                            <Button size="sm">
                                <Plus className="size-4" />
                                {t('family.addMember')}
                            </Button>
                        }
                    />
                )}
            </div>

            <div className={cardGrid}>
                {members.map((member) => (
                    <div key={member.id} className="bg-card flex items-center gap-3 rounded-2xl border-[1.5px] p-3">
                        <div
                            className={cn(
                                'flex size-12 shrink-0 items-center justify-center rounded-full text-base font-bold',
                                !member.color && 'bg-muted',
                            )}
                            style={member.color ? { backgroundColor: member.color, color: contrastTextColor(member.color) } : undefined}
                        >
                            {member.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2">
                                <span className="truncate text-base font-bold">{member.name}</span>
                                <Badge variant="secondary">{t(`role.${member.role}`)}</Badge>
                            </div>
                            <div className="text-muted-foreground text-sm">
                                @{member.username} · {member.open_count} {t('family.openTasks')}
                            </div>
                        </div>

                        {can.manageUsers && (
                            <div className="flex shrink-0 items-center gap-1">
                                <MemberDialog
                                    member={member}
                                    trigger={
                                        <Button size="icon" variant="ghost" aria-label={t('common.edit')}>
                                            <Pencil className="size-4" />
                                        </Button>
                                    }
                                />
                                <Button size="icon" variant="ghost" aria-label={t('common.delete')} onClick={() => remove(member)}>
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </CheckstuLayout>
    );
}
