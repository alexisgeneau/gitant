import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageProps } from '@/types';
import { cn } from '@/lib/utils';

export default function Login({ flash, oauth }: PageProps) {
    const { t } = useTranslation();

    const providers = [
        {
            key: 'github' as const,
            href: '/auth/github',
            label: t('auth.login_with_github'),
            className: 'bg-gray-900 hover:bg-gray-700',
            disabledHint: t('auth.oauth_not_configured', { provider: 'GitHub', env: 'GITHUB_CLIENT_ID' }),
            icon: (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fillRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clipRule="evenodd" />
                </svg>
            ),
        },
        {
            key: 'gitlab' as const,
            href: '/auth/gitlab',
            label: t('auth.login_with_gitlab'),
            className: 'bg-orange-600 hover:bg-orange-500',
            disabledHint: t('auth.oauth_not_configured', { provider: 'GitLab', env: 'GITLAB_CLIENT_ID' }),
            icon: (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 0 1-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 0 1 4.82 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0 1 18.6 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.51L23 13.45a.84.84 0 0 1-.35.94z" />
                </svg>
            ),
        },
    ];

    return (
        <GuestLayout>
            <Head title={t('auth.login_title')} />

            <div className="flex items-center justify-center min-h-[calc(100vh-8rem)]">
                <div className="w-full max-w-md">
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-100 dark:border-gray-700">
                        <div className="text-center mb-8">
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                {t('auth.welcome_back')}
                            </h1>
                            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {t('auth.oauth_only_hint')}
                            </p>
                        </div>

                        {flash?.error && (
                            <div className="mb-6 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
                                {flash.error}
                            </div>
                        )}

                        <div className="space-y-3">
                            {providers.map(({ key, href, label, className, disabledHint, icon }) => {
                                const enabled = oauth[key];
                                return enabled ? (
                                    <a
                                        key={key}
                                        href={href}
                                        className={cn(
                                            'flex items-center justify-center gap-3 w-full px-4 py-3 text-white rounded-xl transition font-medium',
                                            className,
                                        )}
                                    >
                                        {icon}
                                        {label}
                                    </a>
                                ) : (
                                    <div key={key} className="relative group">
                                        <button
                                            disabled
                                            className="flex items-center justify-center gap-3 w-full px-4 py-3 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-xl font-medium cursor-not-allowed opacity-60"
                                        >
                                            {icon}
                                            {label}
                                        </button>
                                        <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                            {disabledHint}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <p className="mt-6 text-xs text-center text-gray-400 dark:text-gray-500">
                            {t('auth.terms_notice')}
                        </p>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
