import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { PageProps } from '@/types';

interface Props {
    children: ReactNode;
}

export default function AppLayout({ children }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage<PageProps>().props;
    const user = auth.user!;

    function handleLogout() {
        router.post('/logout');
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
            <nav className="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                <div className="container mx-auto px-4 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-6">
                        <Link href="/" className="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                            {t('app_name')}
                        </Link>
                        <Link
                            href="/bounties"
                            className="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                        >
                            {t('nav.bounties')}
                        </Link>
                    </div>

                    <div className="flex items-center gap-4">
                        <Link
                            href={`/profile/${user.username}`}
                            className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                        >
                            {user.avatar_url && (
                                <img
                                    src={user.avatar_url}
                                    alt={user.username}
                                    className="w-7 h-7 rounded-full"
                                />
                            )}
                            <span>{user.username}</span>
                        </Link>
                        <Link
                            href="/settings"
                            className="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                        >
                            {t('nav.settings')}
                        </Link>
                        <button
                            onClick={handleLogout}
                            className="text-sm text-gray-600 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 transition"
                        >
                            {t('nav.logout')}
                        </button>
                    </div>
                </div>
            </nav>

            <main className="flex-1 container mx-auto px-4 py-8">
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
