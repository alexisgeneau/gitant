import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { FormEvent, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import BountyCard from '@/Components/bounty/BountyCard';
import SeoMeta from '@/Components/SeoMeta';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import type { BountyPagination } from '@/types/bounty';

interface Props extends PageProps {
    bounties: BountyPagination;
    filters: {
        q?: string;
        platform?: string;
        language?: string;
        min_amount?: string;
        max_amount?: string;
        sort?: string;
    };
    languages: string[];
}

export default function BountyIndex({ auth, bounties, filters, languages }: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.q ?? '');

    const Layout = auth.user ? AppLayout : GuestLayout;

    function applyFilter(key: string, value: string) {
        router.get('/bounties', { ...filters, [key]: value || undefined, page: undefined }, {
            preserveState: true,
            replace: true,
        });
    }

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        applyFilter('q', search);
    }

    function clearFilters() {
        router.get('/bounties', {}, { replace: true });
    }

    const hasFilters = Object.values(filters).some(Boolean);

    const indexDescription = bounties.total > 0
        ? `Browse ${bounties.total} open source bounties on GitHub and GitLab. Filter by language, platform, or amount and claim one as a hunter.`
        : 'Browse open source bounties on GitHub and GitLab. Filter by language, platform, or amount and claim one as a hunter.';

    return (
        <Layout>
            <SeoMeta
                title={`${t('nav.bounties')} · Gitant`}
                description={indexDescription}
                jsonLd={{
                    '@context': 'https://schema.org',
                    '@type': 'CollectionPage',
                    name: 'Open bounties on Gitant',
                    description: indexDescription,
                    url: typeof window !== 'undefined' ? window.location.href : undefined,
                }}
            />

            <div className="max-w-5xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            {t('nav.bounties')}
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {t('bounty.catalogue_subtitle')}
                        </p>
                    </div>
                    {auth.user && (
                        <Link href="/bounties/create">
                            <Button>{t('bounty.create')}</Button>
                        </Link>
                    )}
                </div>

                {/* Filters bar */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 space-y-3">
                    {/* Search */}
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t('bounty.search_placeholder')}
                            className="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <Button type="submit" size="sm">{t('bounty.search')}</Button>
                    </form>

                    {/* Filter chips */}
                    <div className="flex flex-wrap gap-2">
                        {/* Platform filter */}
                        {(['github', 'gitlab'] as const).map((p) => (
                            <button
                                key={p}
                                onClick={() => applyFilter('platform', filters.platform === p ? '' : p)}
                                className={cn(
                                    'text-xs px-3 py-1 rounded-full border transition',
                                    filters.platform === p
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400'
                                )}
                            >
                                {p === 'github' ? 'GitHub' : 'GitLab'}
                            </button>
                        ))}

                        {/* Language filters */}
                        {languages.slice(0, 8).map((lang) => (
                            <button
                                key={lang}
                                onClick={() => applyFilter('language', filters.language === lang ? '' : lang)}
                                className={cn(
                                    'text-xs px-3 py-1 rounded-full border transition',
                                    filters.language === lang
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400'
                                )}
                            >
                                {lang}
                            </button>
                        ))}

                        {hasFilters && (
                            <button
                                onClick={clearFilters}
                                className="text-xs px-3 py-1 rounded-full border border-red-300 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                            >
                                {t('bounty.clear_filters')}
                            </button>
                        )}
                    </div>

                    {/* Sort */}
                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span>{t('bounty.sort_by')}:</span>
                        {(['amount', 'created_at', 'expires_at'] as const).map((s) => (
                            <button
                                key={s}
                                onClick={() => applyFilter('sort', s)}
                                className={cn(
                                    'px-2 py-0.5 rounded transition',
                                    (filters.sort ?? 'amount') === s
                                        ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                                        : 'hover:text-indigo-500'
                                )}
                            >
                                {t(`bounty.sort.${s}`)}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Results count */}
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {t('bounty.results_count', { count: bounties.total })}
                </p>

                {/* Bounty list */}
                {bounties.data.length === 0 ? (
                    <div className="text-center py-16 text-gray-400 dark:text-gray-500">
                        <p className="text-lg">{t('bounty.no_results')}</p>
                        {hasFilters && (
                            <button onClick={clearFilters} className="mt-2 text-sm text-indigo-500 hover:underline">
                                {t('bounty.clear_filters')}
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="space-y-3">
                        {bounties.data.map((bounty) => (
                            <BountyCard key={bounty.id} bounty={bounty} />
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {bounties.last_page > 1 && (
                    <div className="flex justify-center gap-1 mt-8">
                        {bounties.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={cn(
                                    'px-3 py-1.5 text-sm rounded-lg border transition',
                                    link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed'
                                )}
                            />
                        ))}
                    </div>
                )}

                {/* RSS link */}
                <div className="text-center mt-8">
                    <a
                        href="/bounties/rss"
                        className="text-xs text-gray-400 dark:text-gray-500 hover:text-orange-500 transition"
                    >
                        RSS Feed
                    </a>
                </div>
            </div>
        </Layout>
    );
}
