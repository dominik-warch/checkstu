import { useEffect, useState } from 'react';

export function useServiceWorkerUpdate() {
    const [waitingWorker, setWaitingWorker] = useState<ServiceWorker | null>(null);

    useEffect(() => {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        let registration: ServiceWorkerRegistration | undefined;

        const handleUpdateFound = () => {
            const installing = registration?.installing;
            if (!installing) {
                return;
            }

            installing.addEventListener('statechange', () => {
                if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                    setWaitingWorker(installing);
                }
            });
        };

        navigator.serviceWorker.register('/sw.js').then((reg) => {
            registration = reg;

            if (reg.waiting && navigator.serviceWorker.controller) {
                setWaitingWorker(reg.waiting);
            }

            reg.addEventListener('updatefound', handleUpdateFound);
        });

        return () => {
            registration?.removeEventListener('updatefound', handleUpdateFound);
        };
    }, []);

    const applyUpdate = () => {
        if (!waitingWorker) {
            return;
        }

        navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload(), { once: true });
        waitingWorker.postMessage('SKIP_WAITING');
    };

    return { updateAvailable: waitingWorker !== null, applyUpdate };
}
