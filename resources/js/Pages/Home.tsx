import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Home() {
    const { t } = useTranslation();

    return (
        <>
            <Head title="Home" />
            <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-white dark:from-gray-900 dark:to-gray-800">
                <div className="container mx-auto px-4 py-16">
                    <h1 className="text-4xl font-bold text-gray-900 dark:text-white">
                        {t('app_name')}
                    </h1>
                    <p className="mt-4 text-xl text-gray-600 dark:text-gray-300">
                        Bounty platform for open source issues
                    </p>
                    <div className="mt-8 flex gap-4">
                        <a
                            href="/auth/github"
                            className="inline-flex items-center px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-700 transition"
                        >
                            {t('auth.login_with_github')}
                        </a>
                        <a
                            href="/auth/gitlab"
                            className="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-500 transition"
                        >
                            {t('auth.login_with_gitlab')}
                        </a>
                    </div>
                </div>
            </div>
        </>
    );
}
