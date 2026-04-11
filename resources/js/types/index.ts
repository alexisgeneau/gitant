export interface AuthUser {
    id: number;
    username: string;
    email: string | null;
    avatar_url: string | null;
    is_admin: boolean;
    preferred_locale: string;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
    };
    locale: string;
    flash: {
        success: string | null;
        error: string | null;
    };
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = SharedProps & T;
