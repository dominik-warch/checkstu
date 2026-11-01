import { ChevronDown } from 'lucide-react';

import { cn } from '@/lib/utils';

interface PillSelectProps<T extends string> {
    label: string;
    options: { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
    /** Value that means "no filter"; any other value marks the pill as active. */
    neutral?: T;
}

/** A filter as one compact pill: the native select does the picking, so it works with every input method. */
export default function PillSelect<T extends string>({ label, options, value, onChange, neutral }: PillSelectProps<T>) {
    const active = neutral !== undefined && value !== neutral;

    return (
        <label
            className={cn(
                'relative inline-flex h-10 min-w-0 shrink items-center gap-1 rounded-full border-[1.5px] pr-3 pl-4 text-sm transition-colors',
                active ? 'bg-foreground text-background border-foreground font-bold' : 'bg-card hover:border-foreground/40 font-medium',
            )}
        >
            <span className="sr-only">{label}</span>
            <span className="truncate">{options.find((o) => o.value === value)?.label}</span>
            <ChevronDown className="size-4 shrink-0" />
            <select
                value={value}
                onChange={(e) => onChange(e.target.value as T)}
                className="absolute inset-0 cursor-pointer opacity-0"
                aria-label={label}
            >
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
