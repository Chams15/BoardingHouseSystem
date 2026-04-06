import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Room = {
    room_id: number;
    room_number: string;
    category: string;
    price_monthly: string;
    capacity: number;
    status: 'Available' | 'Occupied' | 'Maintenance';
    amenities: string | null;
};

type Props = {
    room?: Room;
    onCancel?: () => void;
};

export default function RoomForm({ room, onCancel }: Props) {
    const isEdit = !!room;

    const { data, setData, post, put, processing, errors } = useForm({
        room_number: room?.room_number ?? '',
        category: room?.category ?? '',
        price_monthly: room?.price_monthly ?? '',
        capacity: room?.capacity ?? '',
        status: room?.status ?? 'Available',
        amenities: room?.amenities ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            put(`/admin/rooms/${room!.room_id}`, {
                onSuccess: () => {
                    // Success message will be shown via flash
                },
            });
        } else {
            post('/admin/rooms', {
                onSuccess: () => {
                    // Success message will be shown via flash
                },
            });
        }
    };

    return (
        <div className="max-w-2xl">
            <form
                onSubmit={handleSubmit}
                className="space-y-6 rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 p-6 shadow-sm"
            >
                    {/* Room Number */}
                    <div>
                        <Label htmlFor="room_number" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Room Number <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="room_number"
                            type="text"
                            value={data.room_number}
                            onChange={(e) => setData('room_number', e.target.value)}
                            placeholder="e.g., 102"
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.room_number && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.room_number}</p>
                        )}
                    </div>

                    {/* Category */}
                    <div>
                        <Label htmlFor="category" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="category"
                            type="text"
                            value={data.category}
                            onChange={(e) => setData('category', e.target.value)}
                            placeholder="e.g., Standard, Deluxe"
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.category && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.category}</p>
                        )}
                    </div>

                    {/* Price Monthly */}
                    <div>
                        <Label htmlFor="price_monthly" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Price Monthly (₱) <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="price_monthly"
                            type="number"
                            step="0.01"
                            value={data.price_monthly}
                            onChange={(e) => setData('price_monthly', e.target.value)}
                            placeholder="0.00"
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.price_monthly && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.price_monthly}</p>
                        )}
                    </div>

                    {/* Capacity */}
                    <div>
                        <Label htmlFor="capacity" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Capacity (persons) <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="capacity"
                            type="number"
                            value={data.capacity}
                            onChange={(e) => setData('capacity', e.target.value)}
                            placeholder="1"
                            className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100"
                        />
                        {errors.capacity && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.capacity}</p>
                        )}
                    </div>

                    {/* Status */}
                    <div>
                        <Label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status <span className="text-red-500">*</span>
                        </Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value as 'Available' | 'Occupied' | 'Maintenance')}>
                            <SelectTrigger className="dark:bg-neutral-800 dark:border-neutral-700 dark:text-gray-100">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className="dark:bg-neutral-800 dark:border-neutral-700">
                                <SelectItem value="Available">Available</SelectItem>
                                <SelectItem value="Occupied">Occupied</SelectItem>
                                <SelectItem value="Maintenance">Maintenance</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.status && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.status}</p>
                        )}
                    </div>

                    {/* Amenities */}
                    <div>
                        <Label htmlFor="amenities" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Amenities (optional)
                        </Label>
                        <textarea
                            id="amenities"
                            value={data.amenities}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('amenities', e.target.value)}
                            placeholder="e.g., WiFi, AC, Hot water, etc."
                            className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-normal text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                            rows={4}
                        />
                        {errors.amenities && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.amenities}</p>
                        )}
                    </div>

                    {/* Submit Button */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            {processing ? 'Saving...' : isEdit ? 'Update Room' : 'Create Room'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onCancel ? onCancel : () => window.location.href = '/admin/rooms'}
                            className="dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800"
                        >
                            Cancel
                        </Button>
                    </div>
            </form>
        </div>
    );
}
