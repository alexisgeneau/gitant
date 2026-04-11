import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

export interface AppNotification {
    id: string;
    type: string | null;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

interface NotificationsState {
    notifications: AppNotification[];
    unreadCount: number;
    loading: boolean;
}

export function useNotifications(userId: number | null) {
    const [state, setState] = useState<NotificationsState>({
        notifications: [],
        unreadCount: 0,
        loading: false,
    });

    const fetchNotifications = useCallback(async () => {
        if (!userId) return;
        setState(s => ({ ...s, loading: true }));
        try {
            const { data } = await axios.get('/api/notifications');
            setState({
                notifications: data.notifications,
                unreadCount: data.unread_count,
                loading: false,
            });
        } catch {
            setState(s => ({ ...s, loading: false }));
        }
    }, [userId]);

    const markRead = useCallback(async (id: string) => {
        await axios.patch(`/api/notifications/${id}/read`);
        setState(s => ({
            ...s,
            notifications: s.notifications.map(n =>
                n.id === id ? { ...n, read_at: new Date().toISOString() } : n
            ),
            unreadCount: Math.max(0, s.unreadCount - 1),
        }));
    }, []);

    const markAllRead = useCallback(async () => {
        await axios.post('/api/notifications/read-all');
        setState(s => ({
            ...s,
            notifications: s.notifications.map(n => ({
                ...n,
                read_at: n.read_at ?? new Date().toISOString(),
            })),
            unreadCount: 0,
        }));
    }, []);

    const prependNotification = useCallback((notification: AppNotification) => {
        setState(s => ({
            ...s,
            notifications: [notification, ...s.notifications],
            unreadCount: s.unreadCount + 1,
        }));
    }, []);

    // Initial load
    useEffect(() => {
        fetchNotifications();
    }, [fetchNotifications]);

    // WebSocket subscription via Laravel Echo (Reverb)
    useEffect(() => {
        if (!userId || typeof window === 'undefined') return;

        // Echo is initialised globally in bootstrap.ts
        const echo = (window as unknown as { Echo?: { private: (ch: string) => { notification: (cb: (n: unknown) => void) => void; stopListening: (event: string) => void } } }).Echo;
        if (!echo) return;

        const channel = echo.private(`user.${userId}`);
        channel.notification((notification: unknown) => {
            prependNotification(notification as AppNotification);
        });

        return () => {
            channel.stopListening('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated');
        };
    }, [userId, prependNotification]);

    return { ...state, markRead, markAllRead, refetch: fetchNotifications };
}
