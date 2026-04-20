export type User = {
    user_id: number;
    role: string;
    email: string;
    is_active: boolean;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    tenant_profile?: {
        profile_id: number;
        full_name: string;
        contact_number: string;
        contact_address?: string | null;
        id_doc_url: string | null;
        emergency_contact: string | null;
        verification_status?: 'Not_Submitted' | 'Pending' | 'Approved' | 'Rejected';
        verification_note?: string | null;
        verification_submitted_at?: string | null;
        verified_at?: string | null;
        verified_by?: number | null;
    };
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    hasTenantRoom?: boolean;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
