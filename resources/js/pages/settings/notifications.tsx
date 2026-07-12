import { Head, usePage } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { usePushSubscription } from '@/hooks/use-push-subscription';
import SettingsLayout from '@/layouts/settings/layout';
import { t } from '@/lib/i18n';
import { type SharedData } from '@/types';

export default function Notifications() {
    const { vapidPublicKey } = usePage<SharedData>().props;
    const { supported, permission, isSubscribed, isLoading, subscribe, unsubscribe } = usePushSubscription(vapidPublicKey);

    return (
        <SettingsLayout>
            <Head title={t('notifications.title')} />

            <div className="space-y-6">
                <HeadingSmall title={t('notifications.title')} description={t('notifications.description')} />

                {!supported && <p className="text-muted-foreground text-sm">{t('notifications.unsupported')}</p>}

                {supported && permission === 'denied' && <p className="text-destructive text-sm">{t('notifications.permissionDenied')}</p>}

                {supported && permission !== 'denied' && (
                    <div className="flex items-center gap-4">
                        <Button disabled={isLoading} onClick={isSubscribed ? unsubscribe : subscribe}>
                            {isSubscribed ? t('notifications.disable') : t('notifications.enable')}
                        </Button>
                        {isSubscribed && <p className="text-sm text-green-600">{t('notifications.enabled')}</p>}
                    </div>
                )}
            </div>
        </SettingsLayout>
    );
}
