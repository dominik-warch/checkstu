import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

const Dialog = DialogPrimitive.Root;

const DialogTrigger = DialogPrimitive.Trigger;

const DialogPortal = DialogPrimitive.Portal;

const DialogClose = DialogPrimitive.Close;

const DialogOverlay = React.forwardRef<
    React.ElementRef<typeof DialogPrimitive.Overlay>,
    React.ComponentPropsWithoutRef<typeof DialogPrimitive.Overlay>
>(({ className, ...props }, ref) => (
    <DialogPrimitive.Overlay
        ref={ref}
        className={cn(
            'fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
            className,
        )}
        {...props}
    />
));
DialogOverlay.displayName = DialogPrimitive.Overlay.displayName;

// iOS shrinks neither the layout viewport nor dvh when the keyboard opens, so the sheet would stay
// full height behind it with nothing to scroll; size and pin it to the visible area instead.
function useVisualViewportVars(): React.CSSProperties | undefined {
    const [vars, setVars] = React.useState<React.CSSProperties>();

    React.useEffect(() => {
        const viewport = window.visualViewport;
        if (!viewport) return;
        const update = () =>
            setVars({
                '--vv-height': `${viewport.height}px`,
                '--vv-bottom': `${Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop)}px`,
            } as React.CSSProperties);
        update();
        viewport.addEventListener('resize', update);
        viewport.addEventListener('scroll', update);
        return () => {
            viewport.removeEventListener('resize', update);
            viewport.removeEventListener('scroll', update);
        };
    }, []);

    return vars;
}

const DialogContent = React.forwardRef<
    React.ElementRef<typeof DialogPrimitive.Content>,
    React.ComponentPropsWithoutRef<typeof DialogPrimitive.Content>
>(({ className, children, style, ...props }, ref) => {
    const viewportVars = useVisualViewportVars();

    return (
    <DialogPortal>
        <DialogOverlay />
        <DialogPrimitive.Content
            ref={ref}
            className={cn(
                // Anchored near the top rather than vertically centered: centering math
                // (top-50%/translate-y-50%) is relative to the layout viewport, which on iOS
                // Safari doesn't shrink when the keyboard opens — the dialog would stay put
                // while the keyboard covers its lower half. On mobile the bottom sheet is
                // sized and pinned via the --vv-* vars from useVisualViewportVars.
                //
                // flex flex-col (not grid): a grid track sizes to its content's min-content
                // width by default, so a long unbreakable string in a descendant (e.g. a movie
                // title) can blow the dialog wider than max-w-lg. Flex items stretched to the
                // container width don't have that blowout — overflowing content stays contained
                // instead of growing the box, which is what overflow-x-hidden here relies on.
                'fixed inset-x-0 bottom-[var(--vv-bottom,0px)] z-50 flex max-h-[calc(var(--vv-height,100dvh)-env(safe-area-inset-top)-1rem)] w-full flex-col gap-4 overflow-x-hidden overflow-y-auto rounded-t-3xl bg-background p-5 pb-[calc(env(safe-area-inset-bottom)+1.25rem)] shadow-[0_-8px_32px_rgb(0_0_0/0.18)] duration-300 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom sm:inset-x-auto sm:bottom-auto sm:left-[50%] sm:top-[max(2rem,env(safe-area-inset-top))] sm:max-h-[calc(100dvh-4rem)] sm:max-w-lg sm:translate-x-[-50%] sm:rounded-3xl sm:p-6',
                className,
            )}
            style={{ ...viewportVars, ...style }}
            {...props}
        >
            {children}
            <DialogPrimitive.Close className="absolute right-3 top-3 grid size-11 place-items-center rounded-full opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-hidden focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground">
                <X className="size-5" />
                <span className="sr-only">Schließen</span>
            </DialogPrimitive.Close>
        </DialogPrimitive.Content>
    </DialogPortal>
    );
});
DialogContent.displayName = DialogPrimitive.Content.displayName;

const DialogHeader = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
    <div className={cn('flex flex-col space-y-1.5 text-center sm:text-left', className)} {...props} />
);
DialogHeader.displayName = 'DialogHeader';

const DialogFooter = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
    <div className={cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2', className)} {...props} />
);
DialogFooter.displayName = 'DialogFooter';

const DialogTitle = React.forwardRef<React.ElementRef<typeof DialogPrimitive.Title>, React.ComponentPropsWithoutRef<typeof DialogPrimitive.Title>>(
    ({ className, ...props }, ref) => (
        <DialogPrimitive.Title ref={ref} className={cn('text-lg font-bold leading-none tracking-tight', className)} {...props} />
    ),
);
DialogTitle.displayName = DialogPrimitive.Title.displayName;

const DialogDescription = React.forwardRef<
    React.ElementRef<typeof DialogPrimitive.Description>,
    React.ComponentPropsWithoutRef<typeof DialogPrimitive.Description>
>(({ className, ...props }, ref) => <DialogPrimitive.Description ref={ref} className={cn('text-sm text-muted-foreground', className)} {...props} />);
DialogDescription.displayName = DialogPrimitive.Description.displayName;

export { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogPortal, DialogTitle, DialogTrigger };
