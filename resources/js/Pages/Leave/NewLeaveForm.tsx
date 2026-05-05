import { useForm } from '@inertiajs/react';
import { X } from 'lucide-react';

type LeaveType = {
    id: number;
    code: string;
    name: string;
    default_balance_days: number | null;
    requires_certificate_after_days: number | null;
    advance_notice_days: number | null;
};

type FormData = {
    leave_type_id: string;
    start_date: string;
    end_date: string;
    reason: string;
    attachment: File | null;
};

type Props = {
    leaveTypes: LeaveType[];
    onClose: () => void;
};

export default function NewLeaveForm({ leaveTypes, onClose }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        leave_type_id: '',
        start_date: '',
        end_date: '',
        reason: '',
        attachment: null,
    });

    const selectedType = leaveTypes.find((t) => String(t.id) === data.leave_type_id) ?? null;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/my-leave', {
            forceFormData: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    }

    return (
        <div className="border-t border-yzh-bone-soft pt-5">
            <div className="mb-6 flex items-baseline gap-3">
                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    01
                </span>
                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                    New leave request
                </span>
                <button
                    type="button"
                    onClick={onClose}
                    className="ml-auto inline-flex min-h-[44px] min-w-[44px] items-center justify-center text-yzh-slate hover:text-yzh-ink"
                    aria-label="Close"
                >
                    <X size={16} />
                </button>
            </div>

            <form onSubmit={submit} className="max-w-lg space-y-5">
                <div className="flex flex-col gap-1">
                    <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                        Leave type
                    </label>
                    <select
                        value={data.leave_type_id}
                        onChange={(e) => setData('leave_type_id', e.target.value)}
                        className="min-h-[44px] border border-yzh-bone-soft bg-white px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                    >
                        <option value="">Select a leave type</option>
                        {leaveTypes.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                    {errors.leave_type_id && (
                        <p className="text-xs text-red-600">{errors.leave_type_id}</p>
                    )}
                    {selectedType?.default_balance_days != null && (
                        <p className="font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-gold">
                            Balance: {selectedType.default_balance_days} days
                        </p>
                    )}
                    {selectedType?.advance_notice_days && (
                        <p className="font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-slate">
                            Requires {selectedType.advance_notice_days}-day advance notice
                        </p>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="flex flex-col gap-1">
                        <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                            Start date
                        </label>
                        <input
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)}
                            className="min-h-[44px] border border-yzh-bone-soft px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                        />
                        {errors.start_date && (
                            <p className="text-xs text-red-600">{errors.start_date}</p>
                        )}
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                            End date
                        </label>
                        <input
                            type="date"
                            value={data.end_date}
                            onChange={(e) => setData('end_date', e.target.value)}
                            className="min-h-[44px] border border-yzh-bone-soft px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                        />
                        {errors.end_date && (
                            <p className="text-xs text-red-600">{errors.end_date}</p>
                        )}
                    </div>
                </div>

                {(errors as Record<string, string>).days_count && (
                    <p className="text-xs text-red-600">
                        {(errors as Record<string, string>).days_count}
                    </p>
                )}

                <div className="flex flex-col gap-1">
                    <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                        Reason (optional)
                    </label>
                    <textarea
                        value={data.reason}
                        onChange={(e) => setData('reason', e.target.value)}
                        rows={3}
                        className="border border-yzh-bone-soft px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                    />
                </div>

                {selectedType?.requires_certificate_after_days != null && (
                    <div className="flex flex-col gap-1">
                        <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                            Medical certificate (required for{' '}
                            {selectedType.requires_certificate_after_days}+ days)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            onChange={(e) => setData('attachment', e.target.files?.[0] ?? null)}
                            className="min-h-[44px] border border-yzh-bone-soft px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                        />
                        {errors.attachment && (
                            <p className="text-xs text-red-600">{errors.attachment}</p>
                        )}
                    </div>
                )}

                <div className="flex gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex min-h-[44px] items-center gap-2 border border-yzh-gold px-5 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? 'Submitting…' : 'Submit request'}
                    </button>
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex min-h-[44px] items-center gap-2 border border-yzh-bone-soft px-5 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}
