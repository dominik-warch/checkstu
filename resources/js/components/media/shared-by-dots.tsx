import type { SharedByMember } from '@/types/media';

interface SharedByDotsProps {
    members: SharedByMember[];
}

/** Compact list-view indicator that other household members also have this item — one dot per member, tinted with their color. */
export default function SharedByDots({ members }: SharedByDotsProps) {
    if (members.length === 0) return null;

    return (
        <span className="inline-flex items-center gap-0.5" title={members.map((member) => member.name).join(', ')}>
            {members.map((member) => (
                <span key={member.id} className="size-2 rounded-full" style={{ background: member.color ?? '#999' }} />
            ))}
        </span>
    );
}
