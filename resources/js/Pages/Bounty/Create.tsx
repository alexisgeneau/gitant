import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { PageProps } from '@/types';
import type { IssueMetadata } from '@/types/bounty';

interface FormData {
    issue_url: string;
    amount_cents: string;
    public_message: string;
    [key: string]: string;
}

export default function BountyCreate(_props: PageProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<FormData>({
        issue_url: '',
        amount_cents: '',
        public_message: '',
    });

    const [resolving, setResolving] = useState(false);
    const [resolveError, setResolveError] = useState<string | null>(null);
    const [metadata, setMetadata] = useState<IssueMetadata | null>(null);
    const resolveTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Debounced issue resolution
    useEffect(() => {
        if (resolveTimer.current) clearTimeout(resolveTimer.current);
        setMetadata(null);
        setResolveError(null);

        const url = data.issue_url.trim();
        if (!url || !/^https:\/\/(github|gitlab)\.com\/.+\/issues\/\d+/i.test(url)) return;

        resolveTimer.current = setTimeout(async () => {
            setResolving(true);
            try {
                const res = await axios.post('/api/bounties/resolve-issue', { url });
                setMetadata(res.data as IssueMetadata);
            } catch (err: unknown) {
                if (axios.isAxiosError(err) && err.response?.data?.error) {
                    setResolveError(err.response.data.error as string);
                } else {
                    setResolveError(t('bounty.resolve_error'));
                }
            } finally {
                setResolving(false);
            }
        }, 600);

        return () => {
            if (resolveTimer.current) clearTimeout(resolveTimer.current);
        };
    }, [data.issue_url]);

    const amountDollars = data.amount_cents ? (parseInt(data.amount_cents) / 100).toFixed(2) : '';
    const commissionDollars = data.amount_cents
        ? (Math.round(parseInt(data.amount_cents) * 0.1) / 100).toFixed(2)
        : '';
    const totalDollars = data.amount_cents
        ? ((parseInt(data.amount_cents) + Math.round(parseInt(data.amount_cents) * 0.1)) / 100).toFixed(2)
        : '';

    function handleAmountChange(e: React.ChangeEvent<HTMLInputElement>) {
        const raw = e.target.value.replace(/[^0-9.]/g, '');
        const dollars = parseFloat(raw);
        if (!isNaN(dollars)) {
            setData('amount_cents', String(Math.round(dollars * 100)));
        } else {
            setData('amount_cents', '');
        }
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/bounties');
    }

    return (
        <AppLayout>
            <Head title={t('bounty.create')} />

            <div className="max-w-2xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    {t('bounty.create')}
                </h1>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Issue URL */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {t('bounty.issue_url_label')}
                        </label>
                        <input
                            type="url"
                            value={data.issue_url}
                            onChange={(e) => setData('issue_url', e.target.value)}
                            placeholder="https://github.com/owner/repo/issues/123"
                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.issue_url && (
                            <p className="mt-1 text-sm text-red-500">{errors.issue_url}</p>
                        )}
                        {resolveError && (
                            <p className="mt-1 text-sm text-red-500">{resolveError}</p>
                        )}
                        {resolving && (
                            <p className="mt-1 text-sm text-gray-400 dark:text-gray-500 animate-pulse">
                                {t('bounty.resolving_issue')}…
                            </p>
                        )}
                    </div>

                    {/* Issue preview */}
                    {metadata && (
                        <div className="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-4">
                            <div className="flex items-center gap-2 mb-2">
                                <span className="text-xs font-medium bg-gray-900 text-white px-2 py-0.5 rounded-full">
                                    {metadata.issue_platform === 'github' ? 'GitHub' : 'GitLab'}
                                </span>
                                {metadata.issue_language && (
                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                        {metadata.issue_language}
                                    </span>
                                )}
                            </div>
                            <p className="font-semibold text-gray-900 dark:text-white">
                                {metadata.issue_title}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {metadata.issue_repo_owner}/{metadata.issue_repo_name}#{metadata.issue_number}
                            </p>
                            {metadata.issue_labels.length > 0 && (
                                <div className="flex flex-wrap gap-1 mt-2">
                                    {metadata.issue_labels.map((label) => (
                                        <span key={label} className="text-xs bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded">
                                            {label}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Amount */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {t('bounty.amount_label')} <span className="text-gray-400">(min $20, max $50,000)</span>
                        </label>
                        <div className="relative">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input
                                type="number"
                                min="20"
                                max="50000"
                                step="0.01"
                                value={amountDollars}
                                onChange={handleAmountChange}
                                placeholder="100.00"
                                className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 pl-7 pr-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        {errors.amount_cents && (
                            <p className="mt-1 text-sm text-red-500">{errors.amount_cents}</p>
                        )}

                        {/* Commission breakdown */}
                        {amountDollars && (
                            <div className="mt-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 text-sm space-y-1">
                                <div className="flex justify-between text-gray-600 dark:text-gray-300">
                                    <span>{t('bounty.bounty_amount')}</span>
                                    <span>${amountDollars}</span>
                                </div>
                                <div className="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>{t('bounty.platform_fee')} (10%)</span>
                                    <span>${commissionDollars}</span>
                                </div>
                                <div className="flex justify-between font-semibold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-1 mt-1">
                                    <span>{t('bounty.total_charged')}</span>
                                    <span>${totalDollars}</span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Public message */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {t('bounty.public_message_label')}
                            <span className="ml-1 text-xs text-gray-400">({t('common.optional')})</span>
                        </label>
                        <textarea
                            value={data.public_message}
                            onChange={(e) => setData('public_message', e.target.value)}
                            rows={3}
                            maxLength={1000}
                            placeholder={t('bounty.public_message_placeholder')}
                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                        />
                        {errors.public_message && (
                            <p className="mt-1 text-sm text-red-500">{errors.public_message}</p>
                        )}
                    </div>

                    <Button
                        type="submit"
                        disabled={processing || !metadata || !data.amount_cents}
                        className="w-full"
                    >
                        {processing ? t('bounty.posting') + '…' : t('bounty.post_bounty')}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
