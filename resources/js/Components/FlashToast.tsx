import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle, X } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function FlashToast() {
    const { props } = usePage();
    const flash = props.flash as { status?: string | null; error?: string | null } | undefined;
    const status = flash?.status;
    const error = flash?.error;
    const message = status || error;
    const isError = !!error && !status;

    const [visible, setVisible] = useState(false);
    const [current, setCurrent] = useState<string | null>(null);

    useEffect(() => {
        if (message) {
            setCurrent(message);
            setVisible(true);
            const t = setTimeout(() => setVisible(false), 4000);
            return () => clearTimeout(t);
        }
    }, [message]);

    if (!visible || !current) return null;

    return (
        <div
            role="status"
            aria-live="polite"
            className={`fixed bottom-6 right-6 z-50 flex items-start gap-3 rounded-sm border px-4 py-3 shadow-lg max-w-sm animate-fade-in ${
                isError
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-green-200 bg-green-50 text-green-800'
            }`}
        >
            {isError
                ? <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                : <CheckCircle className="h-4 w-4 shrink-0 mt-0.5" />
            }
            <p className="text-sm">{current}</p>
            <button onClick={() => setVisible(false)} className="ml-auto shrink-0 opacity-60 hover:opacity-100">
                <X className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}
