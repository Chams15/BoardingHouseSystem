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

type Props = {
    room: Room;
    tenants: Tenant[];
};

export default function CreateLease({ room, tenants }: Props) {
    return <LeaseForm room={room} tenants={tenants} />;
}
