import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: t('settings.appearanceLight') },
        { value: 'dark', icon: Moon, label: t('settings.appearanceDark') },
        { value: 'system', icon: Monitor, label: t('settings.appearanceSystem') },
    ];

    return (
        <div className={cn('bg-muted inline-flex gap-1 rounded-full p-1', className)} {...props}>
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex h-9 items-center rounded-full px-4 transition-colors',
                        appearance === value ? 'bg-foreground text-background font-bold' : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
