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
        id_doc_url: string | null;
        emergency_contact: string | null;
    };
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
