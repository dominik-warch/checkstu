import { cn } from '@/lib/utils';

/** Filter/nav chip look, shared by ChipRow and the settings nav links. */
export const chipClass = (active: boolean) =>
    cn(
        'flex h-9 shrink-0 items-center rounded-full border-[1.5px] px-4 text-sm transition-colors duration-200',
        active ? 'bg-foreground text-background border-foreground font-bold' : 'bg-card text-foreground hover:border-foreground/40 font-medium',
    );

interface ChipRowProps<T extends string> {
    label: string;
    options: { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
    className?: string;
}

/** One-line filter chips; scrolls sideways on phones instead of wrapping into rows. */
export default function ChipRow<T extends string>({ label, options, value, onChange, className }: ChipRowProps<T>) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className={cn('-mx-4 flex gap-1.5 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:flex-wrap lg:px-0', className)}
        >
            {options.map((o) => (
                <button
                    key={o.value}
                    type="button"
                    role="radio"
                    aria-checked={value === o.value}
                    onClick={() => onChange(o.value)}
                    className={chipClass(value === o.value)}
                >
                    {o.label}
                </button>
            ))}
        </div>
    );
}
