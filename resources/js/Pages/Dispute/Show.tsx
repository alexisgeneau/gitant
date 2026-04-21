import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

interface DisputeData {
    id: string;
    type: string;
    opener_summary: string;
    opener_evidence: string[] | null;
    opener_demand: string;
    respondent_position: string | null;
    respondent_evidence: string[] | null;
    respondent_replied_at: string | null;
    status: string;
    resolution: string | null;
    resolution_notes: string | null;
    resolved_at: string | null;
    response_deadline_at: string | null;
    bounty: {
        id: string;
        issue_title: string;
        issue_repo_owner: string;
        issue_repo_name: string;
        issue_number: number;
    };
    opener: {
        id: number;
        username: string;
        avatar_url: string | null;
    };
    resolver?: {
        username: string;
    } | null;
}

interface Props extends PageProps {
    dispute: DisputeData;
}

const STATUS_LABELS: Record<string, string> = {
    open: 'Open',
    awaiting_response: 'Awaiting Response',
    mediation: 'Mediation',
    arbitration: 'Arbitration',
    resolved: 'Resolved',
};

export default function DisputeShow({ auth, dispute, flash }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        position: '',
        evidence: ['', '', '', '', ''],
    });

    const canRespond = dispute.status === 'awaiting_response' &&
        auth.user &&
        auth.user.id !== dispute.opener.id;

    function handleRespond(e: FormEvent) {
        e.preventDefault();
        transform(current => ({
            position: current.position,
            evidence: current.evidence.filter(url => url.trim() !== ''),
        }));
        post(`/disputes/${dispute.id}/respond`);
    }

    return (
        <AppLayout>
            <Head title={`Dispute — ${dispute.bounty.issue_title}`} />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div>
                    <div className="flex items-center gap-3 mb-2">
                        <span className="text-xs font-medium px-2.5 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            {STATUS_LABELS[dispute.status] ?? dispute.status}
                        </span>
                        {dispute.resolution && (
                            <span className="text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {dispute.resolution.replace(/_/g, ' ')}
                            </span>
                        )}
                    </div>
                    <h1 className="text-xl font-bold text-gray-900 dark:text-white">Dispute</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Bounty:{' '}
                        <Link href={`/bounties/${dispute.bounty.id}`} className="text-indigo-600 dark:text-indigo-400 hover:underline">
                            {dispute.bounty.issue_repo_owner}/{dispute.bounty.issue_repo_name}#{dispute.bounty.issue_number}
                        </Link>
                    </p>
                </div>

                {flash?.success && (
                    <div className="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
                        {flash.success}
                    </div>
                )}

                {/* Opener's case */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 className="font-semibold text-gray-900 dark:text-white mb-1">
                        Opened by @{dispute.opener.username}
                    </h2>
                    <p className="text-xs text-gray-400 mb-3 uppercase tracking-wide">{dispute.type.replace(/_/g, ' ')}</p>
                    <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{dispute.opener_summary}</p>
                    {dispute.opener_evidence && dispute.opener_evidence.length > 0 && (
                        <ul className="mt-3 space-y-1">
                            {dispute.opener_evidence.map((url, i) => (
                                <li key={i}>
                                    <a href={url} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-500 hover:underline">
                                        Evidence {i + 1} ↗
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                    <p className="mt-3 text-xs text-gray-400">Requested: <strong>{dispute.opener_demand.replace(/_/g, ' ')}</strong></p>
                    {dispute.response_deadline_at && dispute.status !== 'resolved' && (
                        <p className="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                            Response deadline: {new Date(dispute.response_deadline_at).toLocaleDateString()}
                        </p>
                    )}
                </div>

                {/* Respondent's reply */}
                {dispute.respondent_position && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="font-semibold text-gray-900 dark:text-white mb-3">Respondent's Reply</h2>
                        <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{dispute.respondent_position}</p>
                        {dispute.respondent_evidence && dispute.respondent_evidence.length > 0 && (
                            <ul className="mt-3 space-y-1">
                                {dispute.respondent_evidence.map((url, i) => (
                                    <li key={i}>
                                        <a href={url} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-500 hover:underline">
                                            Evidence {i + 1} ↗
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {/* Resolution */}
                {dispute.status === 'resolved' && dispute.resolution_notes && (
                    <div className="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-6">
                        <h2 className="font-semibold text-green-900 dark:text-green-300 mb-2">
                            Resolution: {dispute.resolution?.replace(/_/g, ' ')}
                            {dispute.resolver && ` — by @${dispute.resolver.username}`}
                        </h2>
                        <p className="text-sm text-green-800 dark:text-green-400 whitespace-pre-wrap">{dispute.resolution_notes}</p>
                    </div>
                )}

                {/* Respond form */}
                {canRespond && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h2 className="font-semibold text-gray-900 dark:text-white mb-4">Your Response</h2>
                        <form onSubmit={handleRespond} className="space-y-4">
                            <div>
                                <textarea
                                    value={data.position}
                                    onChange={e => setData('position', e.target.value)}
                                    rows={6}
                                    placeholder="Describe your position (min 50 characters)…"
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.position && <p className="mt-1 text-xs text-red-600">{errors.position}</p>}
                            </div>
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
                                        placeholder={`Evidence link ${idx + 1} (optional)`}
                                        className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                                    />
                                ))}
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Submitting…' : 'Submit Response'}
                            </Button>
                        </form>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
