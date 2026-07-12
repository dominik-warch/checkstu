import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';

import SettingsLayout from '@/layouts/settings/layout';
import { t } from '@/lib/i18n';

export default function Appearance() {
    return (
        <SettingsLayout>
            <Head title={t('settings.appearanceTitle')} />

            <div className="space-y-6">
                <HeadingSmall title={t('settings.appearanceHeading')} description={t('settings.appearanceDescription')} />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    );
}
