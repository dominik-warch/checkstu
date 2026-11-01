/** A Mensch-ärgere-dich-nicht pawn: ball head, cone body, round foot. Solid, in currentColor. */
export default function Pawn({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden>
            <circle cx="12" cy="6.25" r="3.75" />
            <path d="M9.4 10.75h5.2l2.1 7.25H7.3z" />
            <rect x="5.25" y="18.25" width="13.5" height="3.25" rx="1.6" />
        </svg>
    );
}
