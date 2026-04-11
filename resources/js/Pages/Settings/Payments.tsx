import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';

interface Props extends PageProps {
    connectStatus: string | null;
    hasAccount: boolean;
}

function StatusBadge({ status }: { status: string | null }) {
    if (!status || status === 'pending') {
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                Not connected
            </span>
        );
    }
    if (status === 'pending_verification') {
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                Verification pending
            </span>
        );
    }
    if (status === 'active') {
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                Active
            </span>
        );
    }
    return (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
            {status}
        </span>
    );
}

export default function Payments({ connectStatus, hasAccount, flash }: Props) {
    const { t } = useTranslation();

    function handleConnect() {
        router.post('/settings/payments/connect');
    }

    const isActive = connectStatus === 'active';
    const buttonLabel = hasAccount
        ? (connectStatus === 'active' ? t('settings.payments.manage') : t('settings.payments.continue_onboarding'))
        : t('settings.payments.connect_account');

    return (
        <AppLayout>
            <Head title={t('settings.payments.title')} />

            <div className="max-w-2xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-8">
                    {t('settings.payments.title')}
                </h1>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="mb-6 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
                        {flash.error}
                    </div>
                )}

                {/* Connect account section */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div className="flex items-start justify-between mb-4">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                {t('settings.payments.bank_account')}
                            </h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {t('settings.payments.bank_account_description')}
                            </p>
                        </div>
                        <StatusBadge status={connectStatus} />
                    </div>

                    {isActive ? (
                        <p className="text-sm text-green-600 dark:text-green-400 mb-4">
                            {t('settings.payments.active_description')}
                        </p>
                    ) : (
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            {t('settings.payments.kyc_description')}
                        </p>
                    )}

                    {!isActive && (
                        <button
                            type="button"
                            onClick={handleConnect}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors"
                        >
                            {buttonLabel}
                        </button>
                    )}
                </div>

                {/* Info section */}
                <div className="mt-4 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 text-sm">
                    <p className="font-medium mb-1">{t('settings.payments.info_title')}</p>
                    <p>{t('settings.payments.info_description')}</p>
                </div>
            </div>
        </AppLayout>
    );
}
