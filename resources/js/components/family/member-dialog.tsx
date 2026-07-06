import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { t } from '@/lib/i18n';
import type { FamilyMember } from '@/types/checkstu';

interface MemberDialogProps {
    trigger: ReactNode;
    member?: FamilyMember; // present => edit mode
}

export default function MemberDialog({ trigger, member }: MemberDialogProps) {
    const isEdit = Boolean(member);
    const [open, setOpen] = useState(false);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: member?.name ?? '',
        username: member?.username ?? '',
        email: member?.email ?? '',
        password: '',
        role: member?.role ?? 'member',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const onSuccess = () => {
            if (!isEdit) reset();
            setOpen(false);
        };
        if (isEdit && member) {
            patch(route('users.update', member.id), { preserveScroll: true, onSuccess });
        } else {
            post(route('users.store'), { preserveScroll: true, onSuccess });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{isEdit ? t('family.editMember') : t('family.addMember')}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="m-name">{t('family.name')}</Label>
                        <Input id="m-name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="m-username">{t('family.username')}</Label>
                        <Input
                            id="m-username"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value)}
                            autoComplete="off"
                        />
                        <InputError message={errors.username} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="m-email">{t('family.email')}</Label>
                        <Input id="m-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="m-password">{t('family.password')}</Label>
                        <Input
                            id="m-password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            autoComplete="new-password"
                            placeholder={isEdit ? t('family.passwordKeepHint') : undefined}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Rolle</Label>
                        <Select value={data.role} onValueChange={(v) => setData('role', v as 'admin' | 'member' | 'guest')}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="admin">{t('family.roleParent')}</SelectItem>
                                <SelectItem value="member">{t('family.roleChild')}</SelectItem>
                                <SelectItem value="guest">{t('family.roleGuest')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
