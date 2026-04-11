import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

export default function GuestLayout({ children }: Props) {
    const { t } = useTranslation();

    return (
        <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-white dark:from-gray-900 dark:to-gray-800 flex flex-col">
            <nav className="border-b border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm">
                <div className="container mx-auto px-4 h-16 flex items-center justify-between">
                    <Link href="/" className="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                        {t('app_name')}
                    </Link>
                    <div className="flex items-center gap-4">
                        <Link
                            href="/login"
                            className="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                        >
                            {t('nav.login')}
                        </Link>
                    </div>
                </div>
            </nav>

            <main className="flex-1">
                {children}
            </main>

            <footer className="border-t border-gray-200 dark:border-gray-700 py-6">
                <div className="container mx-auto px-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    © {new Date().getFullYear()} {t('app_name')}
                </div>
            </footer>
        </div>
    );
}
