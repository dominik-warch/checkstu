import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-5">
                        <Link href={route('home')} className="flex flex-col items-center gap-3">
                            {/* Four players, four colours: the board this app is built on. */}
                            <span className="flex gap-1.5" aria-hidden>
                                {['#e11d48', '#f59e0b', '#16a34a', '#2563eb'].map((c) => (
                                    <span key={c} className="size-5 rounded-full" style={{ background: c }} />
                                ))}
                            </span>
                            <span className="text-2xl font-bold tracking-tight">checkstu</span>
                        </Link>

                        <div className="space-y-1 text-center">
                            <h1 className="text-base font-bold">{title}</h1>
                            <p className="text-muted-foreground text-center text-sm">{description}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
