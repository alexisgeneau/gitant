import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageProps } from '@/types';

interface ProfileStats {
    bounties_completed: number;
    success_rate: number;
    total_earned: number;
    bounties_posted: number;
    validation_rate: number;
    total_invested: number;
}

interface Profile {
    username: string;
    avatar_url: string | null;
    github_id: string | null;
    gitlab_id: string | null;
    created_at: string;
    stats: ProfileStats;
}

interface Props extends PageProps {
    profile: Profile;
}

export default function ProfileShow({ profile }: Props) {
    const { t } = useTranslation();

    return (
        <GuestLayout>
            <Head title={`@${profile.username}`} />

            <div className="container mx-auto px-4 py-12 max-w-3xl">
                {/* Header */}
                <div className="flex items-center gap-5 mb-8">
                    {profile.avatar_url ? (
                        <img
                            src={profile.avatar_url}
                            alt={profile.username}
                            className="w-20 h-20 rounded-full ring-2 ring-indigo-200 dark:ring-indigo-800"
                        />
                    ) : (
                        <div className="w-20 h-20 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            {profile.username[0].toUpperCase()}
                        </div>
                    )}
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            @{profile.username}
                        </h1>
                        <div className="flex gap-3 mt-1">
                            {profile.github_id && (
                                <span className="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                    GitHub
                                </span>
                            )}
                            {profile.gitlab_id && (
                                <span className="text-xs text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-2 py-0.5 rounded-full">
                                    GitLab
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Hunter stats */}
                <div className="mb-6">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {t('profile.hunter_stats')}
                    </h2>
                    <div className="grid grid-cols-3 gap-4">
                        <StatCard
                            label={t('profile.bounties_completed')}
                            value={profile.stats.bounties_completed}
                        />
                        <StatCard
                            label={t('profile.success_rate')}
                            value={`${profile.stats.success_rate}%`}
                        />
                        <StatCard
                            label={t('profile.total_earned')}
                            value={`€${profile.stats.total_earned}`}
                        />
                    </div>
                </div>

                {/* Funder stats */}
                <div>
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {t('profile.funder_stats')}
                    </h2>
                    <div className="grid grid-cols-3 gap-4">
                        <StatCard
                            label={t('profile.bounties_posted')}
                            value={profile.stats.bounties_posted}
                        />
                        <StatCard
                            label={t('profile.validation_rate')}
                            value={`${profile.stats.validation_rate}%`}
                        />
                        <StatCard
                            label={t('profile.total_invested')}
                            value={`€${profile.stats.total_invested}`}
                        />
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

function StatCard({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 text-center">
            <div className="text-2xl font-bold text-gray-900 dark:text-white">{value}</div>
            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">{label}</div>
        </div>
    );
}
