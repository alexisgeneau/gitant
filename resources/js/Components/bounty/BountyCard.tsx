import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import type { Bounty } from '@/types/bounty';

interface Props {
    bounty: Bounty;
}

const PLATFORM_COLORS: Record<string, string> = {
    github: 'bg-gray-900 text-white',
    gitlab: 'bg-orange-600 text-white',
};

const STATUS_COLORS: Record<string, string> = {
    open: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    claimed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    in_review: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
    disputed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    expired: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

export default function BountyCard({ bounty }: Props) {
    const { t } = useTranslation();
    const amountDollars = (bounty.total_amount_cents / 100).toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
    });

    return (
        <Link
            href={`/bounties/${bounty.id}`}
            className="block rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap mb-2">
                        <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full', PLATFORM_COLORS[bounty.issue_platform])}>
                            {bounty.issue_platform === 'github' ? 'GitHub' : 'GitLab'}
                        </span>
                        <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full', STATUS_COLORS[bounty.status] ?? 'bg-gray-100 text-gray-600')}>
                            {t(`bounty.status.${bounty.status}`)}
                        </span>
                        {bounty.issue_language && (
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                {bounty.issue_language}
                            </span>
                        )}
                    </div>
                    <h3 className="font-semibold text-gray-900 dark:text-white truncate">
                        {bounty.issue_title}
                    </h3>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {bounty.issue_repo_owner}/{bounty.issue_repo_name}#{bounty.issue_number}
                    </p>
                    {bounty.issue_labels && bounty.issue_labels.length > 0 && (
                        <div className="flex flex-wrap gap-1 mt-2">
                            {bounty.issue_labels.slice(0, 4).map((label) => (
                                <span key={label} className="text-xs bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded">
                                    {label}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
                <div className="flex-shrink-0 text-right">
                    <p className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {amountDollars}
                    </p>
                </div>
            </div>
        </Link>
    );
}
