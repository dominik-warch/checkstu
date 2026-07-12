import { useEffect, useState } from 'react';

function urlBase64ToUint8Array(base64: string): Uint8Array {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const base64Safe = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64Safe);

    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

function subscriptionToPayload(subscription: PushSubscription) {
    const json = subscription.toJSON();

    return {
        endpoint: json.endpoint,
        keys: { p256dh: json.keys?.p256dh, auth: json.keys?.auth },
    };
}

/** Laravel's XSRF-TOKEN cookie is readable JS-side by design — axios/Inertia send it back as X-XSRF-TOKEN. */
function xsrfHeader(): Record<string, string> {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

/**
 * Subscribes/unsubscribes the current device for web push. Assumes the
 * service worker is already registered (done app-wide by useServiceWorkerUpdate).
 */
export function usePushSubscription(vapidPublicKey: string | null) {
    const supported = typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window;
    const [permission, setPermission] = useState<NotificationPermission>(supported ? Notification.permission : 'denied');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        if (!supported) {
            return;
        }

        navigator.serviceWorker.ready.then((registration) =>
            registration.pushManager.getSubscription().then((subscription) => setIsSubscribed(subscription !== null)),
        );
    }, [supported]);

    const subscribe = async () => {
        if (!supported || !vapidPublicKey) {
            return;
        }

        setIsLoading(true);
        try {
            const result = await Notification.requestPermission();
            setPermission(result);
            if (result !== 'granted') {
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            await fetch(route('push-subscriptions.store'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
                credentials: 'same-origin',
                body: JSON.stringify(subscriptionToPayload(subscription)),
            });

            setIsSubscribed(true);
        } finally {
            setIsLoading(false);
        }
    };

    const unsubscribe = async () => {
        if (!supported) {
            return;
        }

        setIsLoading(true);
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                setIsSubscribed(false);
                return;
            }

            const payload = subscriptionToPayload(subscription);
            await subscription.unsubscribe();

            await fetch(route('push-subscriptions.destroy'), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
                credentials: 'same-origin',
                body: JSON.stringify({ endpoint: payload.endpoint }),
            });

            setIsSubscribed(false);
        } finally {
            setIsLoading(false);
        }
    };

    return { supported, permission, isSubscribed, isLoading, subscribe, unsubscribe };
}
