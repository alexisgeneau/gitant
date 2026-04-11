import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { PageProps } from '@/types';
import type { Bounty } from '@/types/bounty';
import { FormEvent } from 'react';

interface Props extends PageProps {
    bounty: Bounty;
}

const DISPUTE_TYPES = [
    { value: 'unjustified_rejection', label: 'Unjustified rejection' },
    { value: 'non_conforming_pr', label: 'Non-conforming PR' },
    { value: 'ambiguous_specs', label: 'Ambiguous specifications' },
    { value: 'timing', label: 'Timing issue' },
    { value: 'other', label: 'Other' },
];

const DEMAND_OPTIONS = [
    { value: 'full_payment', label: 'Full payment to hunter' },
    { value: 'full_refund', label: 'Full refund to funder(s)' },
    { value: 'split_75_25', label: '75% hunter / 25% funder(s)' },
    { value: 'split_50_50', label: '50/50 split' },
    { value: 'split_25_75', label: '25% hunter / 75% funder(s)' },
];

export default function DisputeCreate({ bounty }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        type: '',
        summary: '',
        demand: '',
        evidence: ['', '', '', '', ''],
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        const cleanedEvidence = data.evidence.filter(url => url.trim() !== '');
        post(`/bounties/${bounty.id}/dispute`, {
            data: { ...data, evidence: cleanedEvidence },
        });
    }

    return (
        <AppLayout>
            <Head title="Open Dispute" />

            <div className="max-w-2xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Open Dispute</h1>
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-8">
                    For bounty: <strong>{bounty.issue_title}</strong>
                </p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Type */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Dispute type <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.type}
                            onChange={e => setData('type', e.target.value)}
                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Select a type…</option>
                            {DISPUTE_TYPES.map(opt => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                        {errors.type && <p className="mt-1 text-xs text-red-600">{errors.type}</p>}
                    </div>

                    {/* Summary */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Summary <span className="text-red-500">*</span>
                            <span className="ml-1 text-xs text-gray-400 font-normal">(min 50 characters)</span>
                        </label>
                        <textarea
                            value={data.summary}
                            onChange={e => setData('summary', e.target.value)}
                            rows={6}
                            placeholder="Explain the dispute in detail…"
                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.summary && <p className="mt-1 text-xs text-red-600">{errors.summary}</p>}
                    </div>

                    {/* Demand */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Requested resolution <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.demand}
                            onChange={e => setData('demand', e.target.value)}
                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Select a resolution…</option>
                            {DEMAND_OPTIONS.map(opt => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                        {errors.demand && <p className="mt-1 text-xs text-red-600">{errors.demand}</p>}
                    </div>

                    {/* Evidence */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Evidence links <span className="text-xs text-gray-400 font-normal">(optional, max 5 URLs)</span>
                        </label>
                        <div className="space-y-2">
                            {data.evidence.map((url, idx) => (
                                <input
                                    key={idx}
                                    type="url"
                                    value={url}
                                    onChange={e => {
                                        const ev = [...data.evidence];
                                        ev[idx] = e.target.value;
                                        setData('evidence', ev);
                                    }}
                                    placeholder={`Link ${idx + 1} (e.g. PR, comment, screenshot)`}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            ))}
                        </div>
                    </div>

                    {errors.dispute && (
                        <p className="text-sm text-red-600">{errors.dispute}</p>
                    )}

                    <div className="pt-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Submitting…' : 'Open Dispute'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
