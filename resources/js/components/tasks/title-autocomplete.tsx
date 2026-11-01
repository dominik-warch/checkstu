import { useState } from 'react';

import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';
import type { TaskTemplateSummary } from '@/types/checkstu';

interface TitleAutocompleteProps {
    value: string;
    onChange: (value: string) => void;
    templates: TaskTemplateSummary[]; // already sorted most-used first; empty = no suggestions (edit mode)
    error?: string;
}

/**
 * Title field with two ways to reuse a previously-used name (saves typing on
 * recurring chores): a row of the 8 most-used names as tappable chips, and a
 * filter-as-you-type dropdown for anything beyond those 8. Typing a brand-new
 * name works exactly like a plain input — it just isn't suggested yet (every
 * task creation records/bumps its title in the catalogue server-side).
 */
export default function TitleAutocomplete({ value, onChange, templates, error }: TitleAutocompleteProps) {
    const [focused, setFocused] = useState(false);
    const mostUsed = templates.slice(0, 8);

    const query = value.trim().toLowerCase();
    const suggestions =
        query.length > 0 ? templates.filter((tpl) => tpl.name.toLowerCase().includes(query) && tpl.name.toLowerCase() !== query).slice(0, 6) : [];

    return (
        <div className="grid gap-2">
            <Label htmlFor="title">{t('task.title')}</Label>

            {mostUsed.length > 0 && (
                <div className="-mx-5 flex gap-1.5 overflow-x-auto px-5 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0">
                    {mostUsed.map((tpl) => (
                        <button
                            key={tpl.id}
                            type="button"
                            onClick={() => onChange(tpl.name)}
                            className="bg-card hover:border-foreground/40 h-9 shrink-0 rounded-full border-[1.5px] px-4 text-sm font-medium transition-colors"
                        >
                            {tpl.name}
                        </button>
                    ))}
                </div>
            )}

            <div className="relative">
                <Input
                    id="title"
                    autoFocus
                    autoComplete="off"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onFocus={() => setFocused(true)}
                    onBlur={() => setTimeout(() => setFocused(false), 150)} // allow the click below to register first
                    placeholder="z. B. Staubsaugen"
                />
                {focused && suggestions.length > 0 && (
                    <div className="bg-popover text-popover-foreground absolute z-30 mt-1 w-full overflow-hidden rounded-lg border-[1.5px] p-1 shadow-[0_8px_24px_rgb(0_0_0/0.12)]">
                        {suggestions.map((tpl) => (
                            <button
                                key={tpl.id}
                                type="button"
                                onClick={() => {
                                    onChange(tpl.name);
                                    setFocused(false);
                                }}
                                className="hover:bg-muted flex min-h-11 w-full items-center rounded-md px-3 text-left text-base"
                            >
                                {tpl.name}
                            </button>
                        ))}
                    </div>
                )}
            </div>
            <InputError message={error} />
        </div>
    );
}
