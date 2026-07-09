import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useServiceWorkerUpdate } from '@/hooks/use-sw-update';

export function UpdateToast() {
    const { updateAvailable, applyUpdate } = useServiceWorkerUpdate();

    if (!updateAvailable) {
        return null;
    }

    return (
        <div className="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-sm sm:right-4 sm:left-auto">
            <Alert className="bg-background shadow-lg">
                <AlertTitle>Neue Version verfügbar</AlertTitle>
                <AlertDescription className="mt-2 flex items-center justify-between gap-3">
                    <span>Aktualisiere, um die neueste Version zu erhalten.</span>
                    <Button size="sm" onClick={applyUpdate}>
                        Aktualisieren
                    </Button>
                </AlertDescription>
            </Alert>
        </div>
    );
}
