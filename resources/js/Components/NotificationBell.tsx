import { useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { useNotifications, type AppNotification } from '@/hooks/useNotifications';

interface Props {
    userId: number;
}

function NotificationItem({ notification, onRead }: { notification: AppNotification; onRead: (id: string) => void }) {
    const data = notification.data;
    const isUnread = !notification.read_at;

    const label = (() => {
        switch (data.type) {
            case 'bounty_claimed': return `@${data.hunter} claimed "${data.bounty_title}"`;
            case 'pr_submitted': return `PR submitted for "${data.bounty_title}"`;
            case 'pr_approved': return `PR approved for "${data.bounty_title}" — payout incoming!`;
            case 'pr_rejected': return `PR rejected for "${data.bounty_title}"`;
            case 'dispute_opened': return `Dispute opened on "${data.bounty_title}"`;
            case 'auto_validation_reminder': return `${data.days_remaining}d left to review "${data.bounty_title}"`;
            default: return 'New notification';
        }
    })();

    const url = (data.url as string | undefined) ?? '#';

    return (
        <div
            className={`px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 ${isUnread ? 'bg-indigo-50 dark:bg-indigo-900/10' : ''}`}
            onClick={() => {
                if (isUnread) onRead(notification.id);
            }}
        >
            <Link href={url} className="block">
                <p className={`text-sm ${isUnread ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'}`}>
                    {label}
                </p>
                <p className="text-xs text-gray-400 mt-0.5">
                    {new Date(notification.created_at).toLocaleDateString()}
                </p>
            </Link>
        </div>
    );
}

export default function NotificationBell({ userId }: Props) {
    const [open, setOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const { notifications, unreadCount, loading, markRead, markAllRead } = useNotifications(userId);

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                type="button"
                onClick={() => setOpen(o => !o)}
                className="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                aria-label="Notifications"
            >
                {/* Bell icon */}
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute top-1 right-1 w-4 h-4 bg-indigo-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                            Notifications {unreadCount > 0 && <span className="ml-1 text-indigo-600">({unreadCount})</span>}
                        </h3>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="text-xs text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300"
                            >
                                Mark all read
                            </button>
                        )}
                    </div>

                    {/* List */}
                    <div className="max-h-80 overflow-y-auto">
                        {loading && (
                            <p className="px-4 py-6 text-sm text-center text-gray-400">Loading…</p>
                        )}
                        {!loading && notifications.length === 0 && (
                            <p className="px-4 py-6 text-sm text-center text-gray-400">No notifications yet.</p>
                        )}
                        {!loading && notifications.map(n => (
                            <NotificationItem key={n.id} notification={n} onRead={markRead} />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
