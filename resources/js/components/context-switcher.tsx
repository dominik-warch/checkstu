import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { t } from '@/lib/i18n';

interface ContextSwitcherProps {
    context: 'tasks' | 'media';
}

/** Switches between checkstu's two separate app contexts: household tasks and personal media tracking. */
export default function ContextSwitcher({ context }: ContextSwitcherProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-1 text-lg font-semibold tracking-tight">
                {t('common.appName')} · {context === 'tasks' ? t('nav.switchToTasks') : t('nav.switchToMedia')}
                <ChevronDown className="text-muted-foreground size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <DropdownMenuItem asChild>
                    <Link href="/">{t('nav.switchToTasks')}</Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href="/media">{t('nav.switchToMedia')}</Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
