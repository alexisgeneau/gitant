import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';

export default function Dashboard({ auth }: PageProps) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={t('nav.dashboard')} />

            <div className="max-w-4xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {t('dashboard.welcome', { username: auth.user?.username })}
                </h1>
                <p className="text-gray-500 dark:text-gray-400">
                    {t('dashboard.subtitle')}
                </p>
            </div>
        </AppLayout>
    );
}
