import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/Layouts/AppLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import type { Bounty } from '@/types/bounty';

interface Props extends PageProps {
    bounty: Bounty;
    paymentStatus?: string | null;
}

const STATUS_COLORS: Record<string, string> = {
    open: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    claimed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    in_review: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
    disputed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    expired: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

export default function BountyShow({ auth, bounty, paymentStatus, flash }: Props) {
    const { t } = useTranslation();
    const Layout = auth.user ? AppLayout : GuestLayout;

    const isClaimer = auth.user && bounty.claimer?.id === auth.user.id;
    const isFunder = auth.user && bounty.paid_contributions?.some(c => c.funder.id === auth.user!.id);

    function handleClaim() {
        router.post(`/bounties/${bounty.id}/claim`);
    }

    function handleRelease() {
        if (confirm('Are you sure you want to release this claim?')) {
            router.post(`/bounties/${bounty.id}/release`);
        }
    }

    function handleApprove() {
        if (confirm('Approve the hunter\'s work and trigger payout?')) {
            router.post(`/bounties/${bounty.id}/approve`);
        }
    }

    function handleReject(dispute = false) {
        const msg = dispute
            ? 'Open a dispute? An admin will arbitrate.'
            : 'Reject the PR? The hunter can revise and resubmit.';
        if (confirm(msg)) {
            router.post(`/bounties/${bounty.id}/reject`, { dispute: dispute ? 1 : 0 });
        }
    }

    const amountDollars = (bounty.total_amount_cents / 100).toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
    });

    const platformLabel = bounty.issue_platform === 'github' ? 'GitHub' : 'GitLab';
    const issueLink = bounty.issue_url;

    return (
        <Layout>
            <Head title={bounty.issue_title} />

            <div className="max-w-3xl mx-auto">
                {/* Payment status banners */}
                {paymentStatus === 'success' && (
                    <div className="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm font-medium">
                        Payment successful! Your bounty contribution is being confirmed. The total will update shortly.
                    </div>
                )}
                {paymentStatus === 'cancelled' && (
                    <div className="mb-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 text-sm">
                        Payment was cancelled. You can try again by clicking "Add funds".
                    </div>
                )}

                {/* Breadcrumb */}
                <nav className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <Link href="/bounties" className="hover:text-indigo-600 dark:hover:text-indigo-400">
                        {t('nav.bounties')}
                    </Link>
                    <span className="mx-2">/</span>
                    <span className="truncate">{bounty.issue_repo_owner}/{bounty.issue_repo_name}</span>
                </nav>

                {/* Header */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 flex-wrap mb-3">
                                <span className={cn('text-xs font-medium px-2.5 py-1 rounded-full', bounty.issue_platform === 'github' ? 'bg-gray-900 text-white' : 'bg-orange-600 text-white')}>
                                    {platformLabel}
                                </span>
                                <span className={cn('text-xs font-medium px-2.5 py-1 rounded-full', STATUS_COLORS[bounty.status] ?? 'bg-gray-100 text-gray-600')}>
                                    {t(`bounty.status.${bounty.status}`)}
                                </span>
                                {bounty.issue_language && (
                                    <span className="text-xs text-gray-500 dark:text-gray-400 px-2.5 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                                        {bounty.issue_language}
                                    </span>
                                )}
                            </div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                                {bounty.issue_title}
                            </h1>
                            <a
                                href={issueLink}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1 mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                {bounty.issue_repo_owner}/{bounty.issue_repo_name}#{bounty.issue_number}
                                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                        <div className="flex-shrink-0 text-right">
                            <p className="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                {amountDollars}
                            </p>
                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {t('bounty.total_bounty')}
                            </p>
                        </div>
                    </div>

                    {/* Labels */}
                    {bounty.issue_labels && bounty.issue_labels.length > 0 && (
                        <div className="flex flex-wrap gap-1.5 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            {bounty.issue_labels.map((label) => (
                                <span key={label} className="text-xs bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full border border-indigo-100 dark:border-indigo-800">
                                    {label}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                {/* Public message from funder */}
                {bounty.public_message && (
                    <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-4">
                        <p className="text-xs font-medium text-amber-700 dark:text-amber-400 mb-1">
                            {t('bounty.funder_message')}
                        </p>
                        <p className="text-sm text-amber-800 dark:text-amber-300">{bounty.public_message}</p>
                    </div>
                )}

                {/* Issue description */}
                {bounty.issue_description && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-4">
                        <h2 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            {t('bounty.issue_description')}
                        </h2>
                        <div className="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-sm leading-relaxed">
                            {bounty.issue_description.length > 800
                                ? bounty.issue_description.slice(0, 800) + '…'
                                : bounty.issue_description}
                        </div>
                        <a
                            href={issueLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 mt-3 text-xs text-indigo-500 hover:underline"
                        >
                            {t('bounty.view_full_issue')} ↗
                        </a>
                    </div>
                )}

                {/* Claim status */}
                {bounty.claimer && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 flex items-center gap-3">
                        {bounty.claimer.avatar_url && (
                            <img src={bounty.claimer.avatar_url} alt={bounty.claimer.username} className="w-8 h-8 rounded-full" />
                        )}
                        <div>
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                {t('bounty.claimed_by')}{' '}
                                <Link href={`/profile/${bounty.claimer.username}`} className="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    @{bounty.claimer.username}
                                </Link>
                            </p>
                            {bounty.claim_expires_at && (
                                <p className="text-xs text-gray-400 dark:text-gray-500">
                                    {t('bounty.deadline')}: {new Date(bounty.claim_expires_at).toLocaleDateString()}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {/* Flash messages */}
                {flash?.success && (
                    <div className="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
                        {flash.error}
                    </div>
                )}

                {/* Actions */}
                <div className="flex flex-wrap gap-3">
                    {/* Guest */}
                    {!auth.user && (
                        <div className="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span>{t('bounty.login_to_act')}</span>
                            <Link href="/login">
                                <Button size="sm">{t('nav.login')}</Button>
                            </Link>
                        </div>
                    )}

                    {/* Hunter: claim open bounty */}
                    {bounty.status === 'open' && auth.user && !isFunder && (
                        <Button onClick={handleClaim}>
                            {t('bounty.claim')}
                        </Button>
                    )}

                    {/* Funder: add more funds */}
                    {bounty.status === 'open' && auth.user && (
                        <Button variant="outline" asChild>
                            <Link href={`/bounties/create?stack=${encodeURIComponent(bounty.issue_url)}`}>
                                {t('bounty.contribute')}
                            </Link>
                        </Button>
                    )}

                    {/* Hunter: release their own claim */}
                    {bounty.status === 'claimed' && isClaimer && (
                        <Button variant="outline" onClick={handleRelease} className="text-red-600 border-red-300 hover:bg-red-50">
                            {t('bounty.release_claim')}
                        </Button>
                    )}

                    {/* Funder: approve or reject while in_review */}
                    {bounty.status === 'in_review' && isFunder && (
                        <>
                            <Button onClick={handleApprove} className="bg-green-600 hover:bg-green-700">
                                {t('bounty.approve')}
                            </Button>
                            <Button variant="outline" onClick={() => handleReject(false)} className="text-yellow-700 border-yellow-300 hover:bg-yellow-50">
                                {t('bounty.request_changes')}
                            </Button>
                            <Button variant="outline" onClick={() => handleReject(true)} className="text-red-600 border-red-300 hover:bg-red-50">
                                {t('bounty.open_dispute')}
                            </Button>
                        </>
                    )}

                    {/* PR link */}
                    {bounty.linked_pr_url && (
                        <a
                            href={bounty.linked_pr_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 px-3 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                        >
                            {t('bounty.view_pr')} ↗
                        </a>
                    )}
                </div>

                {/* Contributions */}
                {bounty.paid_contributions && bounty.paid_contributions.length > 0 && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mt-4">
                        <h2 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            {t('bounty.funders')} ({bounty.paid_contributions.length})
                        </h2>
                        <div className="space-y-2">
                            {bounty.paid_contributions.map((c) => (
                                <div key={c.id} className="flex items-center justify-between text-sm">
                                    <div className="flex items-center gap-2">
                                        {c.funder.avatar_url && (
                                            <img src={c.funder.avatar_url} alt={c.funder.username} className="w-6 h-6 rounded-full" />
                                        )}
                                        <Link href={`/profile/${c.funder.username}`} className="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            @{c.funder.username}
                                        </Link>
                                    </div>
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        ${(c.amount_cents / 100).toFixed(2)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Layout>
    );
}
