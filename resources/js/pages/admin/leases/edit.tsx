import LeaseForm from './form';

type Tenant = {
    user_id: number;
    email: string;
    tenant_profile?: {
        full_name: string;
        contact_number: string;
    };
};

type Room = {
    room_id: number;
    room_number: string;
    price_monthly: string;
};

type LeaseContract = {
    contract_id: number;
    tenant_id: number;
    room_id: number;
    start_date: string;
    end_date: string;
    security_deposit: string;
    contract_status: string;
};

type Props = {
    lease: LeaseContract & { tenant: Tenant; room: Room };
    tenants: Tenant[];
};

export default function EditLease({ lease, tenants }: Props) {
    return <LeaseForm lease={lease} room={lease.room} tenants={tenants} />;
}
