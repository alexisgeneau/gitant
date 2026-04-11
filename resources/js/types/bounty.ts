export interface Bounty {
    id: string;
    issue_url: string;
    issue_platform: 'github' | 'gitlab';
    issue_repo_owner: string;
    issue_repo_name: string;
    issue_number: number;
    issue_title: string;
    issue_description: string | null;
    issue_labels: string[] | null;
    issue_language: string | null;
    status: 'open' | 'claimed' | 'in_review' | 'completed' | 'disputed' | 'expired' | 'cancelled';
    total_amount_cents: number;
    claimed_by_user_id: number | null;
    claimed_at: string | null;
    claim_expires_at: string | null;
    linked_pr_url: string | null;
    linked_pr_number: number | null;
    pr_submitted_at: string | null;
    auto_validate_at: string | null;
    expires_at: string | null;
    public_message: string | null;
    created_at: string;
    updated_at: string;
    // Relations
    claimer?: {
        id: number;
        username: string;
        avatar_url: string | null;
    } | null;
    paid_contributions?: Array<{
        id: string;
        amount_cents: number;
        funder: {
            id: number;
            username: string;
            avatar_url: string | null;
        };
    }>;
}

export interface BountyPagination {
    data: Bounty[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface IssueMetadata {
    issue_url: string;
    issue_platform: 'github' | 'gitlab';
    issue_repo_owner: string;
    issue_repo_name: string;
    issue_number: number;
    issue_title: string;
    issue_description: string | null;
    issue_labels: string[];
    issue_language: string | null;
}
