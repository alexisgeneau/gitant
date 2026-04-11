import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

interface DisputeSummary {
    id: string;
    type: string;
    opener_demand: string;
    status: string;
    resolution: string | null;
    response_deadline_at: string | null;
    resolved_at: string | null;
    bounty: {
        id: string;
        issue_title: string;
        total_amount_cents: number;
    };
    opener: {
        username: string;
    };
    resolver?: { username: string } | null;
}

interface Pagination {
    data: DisputeSummary[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Props extends PageProps {
    disputes: Pagination;
    resolved: DisputeSummary[];
}

function ResolveForm({ dispute }: { dispute: DisputeSummary }) {
    const { data, setData, post, processing, errors } = useForm({
        resolution: '',
        notes: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (confirm(`Resolve with "${data.resolution}"?`)) {
            post(`/admin/disputes/${dispute.id}/resolve`);
        }
    }

    return (
        <form onSubmit={handleSubmit} className="mt-3 space-y-2 border-t border-gray-100 dark:border-gray-700 pt-3">
            <select
                value={data.resolution}
                onChange={e => setData('resolution', e.target.value)}
                className="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs"
            >
                <option value="">Choose resolution…</option>
                <option value="paid_full">Paid full (hunter wins)</option>
                <option value="refunded_full">Refunded full (funder wins)</option>
                <option value="split_75_25">Split 75/25 (hunter)</option>
                <option value="split_50_50">Split 50/50</option>
                <option value="split_25_75">Split 25/75 (funder)</option>
                <option value="mutual">Mutual agreement</option>
            </select>
            <textarea
                value={data.notes}
                onChange={e => setData('notes', e.target.value)}
                rows={3}
                placeholder="Resolution notes (min 20 characters)…"
                className="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs"
            />
            {errors.resolve && <p className="text-xs text-red-600">{errors.resolve}</p>}
            <Button type="submit" size="sm" disabled={processing}>
                {processing ? 'Resolving…' : 'Resolve Dispute'}
            </Button>
        </form>
    );
}

export default function AdminDisputesIndex({ disputes, resolved, flash }: Props) {
    return (
        <AppLayout>
            <Head title="Admin — Disputes" />

            <div className="max-w-4xl mx-auto">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Disputes — Admin</h1>

                {flash?.success && (
                    <div className="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 text-sm">
                        {flash.success}
                    </div>
                )}

                {/* Active disputes */}
                <section className="mb-8">
                    <h2 className="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Active ({disputes.total})
                    </h2>
                    {disputes.data.length === 0 ? (
                        <p className="text-sm text-gray-500">No active disputes.</p>
                    ) : (
                        <div className="space-y-4">
                            {disputes.data.map(dispute => (
                                <div key={dispute.id} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <Link href={`/disputes/${dispute.id}`} className="text-sm font-medium text-indigo-600 hover:underline">
                                                {dispute.bounty.issue_title}
                                            </Link>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                Opened by @{dispute.opener.username} · {dispute.type.replace(/_/g, ' ')} · Status: {dispute.status}
                                            </p>
                                            <p className="text-xs text-gray-400">
                                                Demand: {dispute.opener_demand.replace(/_/g, ' ')} · Bounty: €{(dispute.bounty.total_amount_cents / 100).toFixed(2)}
                                            </p>
                                            {dispute.response_deadline_at && (
                                                <p className="text-xs text-yellow-600 dark:text-yellow-400">
                                                    Deadline: {new Date(dispute.response_deadline_at).toLocaleDateString()}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <ResolveForm dispute={dispute} />
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                {/* Recently resolved */}
                {resolved.length > 0 && (
                    <section>
                        <h2 className="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Recently Resolved</h2>
                        <div className="space-y-2">
                            {resolved.map(dispute => (
                                <div key={dispute.id} className="flex justify-between items-center bg-gray-50 dark:bg-gray-800/50 rounded-lg px-4 py-3 text-sm">
                                    <Link href={`/disputes/${dispute.id}`} className="text-indigo-600 hover:underline truncate max-w-sm">
                                        {dispute.bounty.issue_title}
                                    </Link>
                                    <div className="text-xs text-gray-500 flex gap-3">
                                        <span>{dispute.resolution?.replace(/_/g, ' ')}</span>
                                        {dispute.resolved_at && <span>{new Date(dispute.resolved_at).toLocaleDateString()}</span>}
                                        {dispute.resolver && <span>by @{dispute.resolver.username}</span>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
