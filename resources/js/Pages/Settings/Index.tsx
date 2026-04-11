import { Head, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

interface SettingsUser {
    username: string;
    email: string | null;
    avatar_url: string | null;
    preferred_locale: string;
    github_id: string | null;
    gitlab_id: string | null;
    stripe_connect_status: string | null;
}

interface Props extends PageProps {
    user: SettingsUser;
}

export default function SettingsIndex({ user, flash }: Props) {
    const { t } = useTranslation();
    const { data, setData, patch, processing, errors } = useForm({
        preferred_locale: user.preferred_locale,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        patch('/settings');
    }

    function handleDeleteAccount() {
        if (confirm(t('settings.delete_confirm'))) {
            router.delete('/settings');
        }
    }

    return (
        <AppLayout>
            <Head title={t('nav.settings')} />

            <div className="max-w-2xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-8">
                    {t('settings.title')}
                </h1>

                {flash?.success && (
                    <div className="mb-6 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
                        {flash.success}
                    </div>
                )}

                {/* Profile section */}
                <section className="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 mb-6">
                    <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">
                        {t('settings.profile')}
                    </h2>
                    <div className="flex items-center gap-4">
                        {user.avatar_url && (
                            <img src={user.avatar_url} alt={user.username} className="w-14 h-14 rounded-full" />
                        )}
                        <div>
                            <div className="font-medium text-gray-900 dark:text-white">@{user.username}</div>
                            {user.email && (
                                <div className="text-sm text-gray-500 dark:text-gray-400">{user.email}</div>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 flex gap-2">
                        {user.github_id && (
                            <span className="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full">
                                {t('settings.connected_github')}
                            </span>
                        )}
                        {user.gitlab_id && (
                            <span className="text-xs bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 px-3 py-1 rounded-full">
                                {t('settings.connected_gitlab')}
                            </span>
                        )}
                    </div>
                </section>

                {/* Preferences */}
                <section className="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 mb-6">
                    <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">
                        {t('settings.preferences')}
                    </h2>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {t('settings.language')}
                            </label>
                            <select
                                value={data.preferred_locale}
                                onChange={(e) => setData('preferred_locale', e.target.value)}
                                className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="en">English</option>
                                <option value="fr">Français</option>
                            </select>
                            {errors.preferred_locale && (
                                <p className="mt-1 text-xs text-red-600">{errors.preferred_locale}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-500 disabled:opacity-50 transition"
                        >
                            {t('settings.save')}
                        </button>
                    </form>
                </section>

                {/* Danger zone */}
                <section className="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-900 p-6">
                    <h2 className="text-base font-semibold text-red-600 dark:text-red-400 mb-4">
                        {t('settings.danger_zone')}
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {t('settings.delete_account_description')}
                    </p>
                    <button
                        onClick={handleDeleteAccount}
                        className="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-500 transition"
                    >
                        {t('settings.delete_account')}
                    </button>
                </section>
            </div>
        </AppLayout>
    );
}
