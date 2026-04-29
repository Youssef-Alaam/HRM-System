import { Eye, EyeOff } from 'lucide-react';
import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    isFocused?: boolean;
};

export default forwardRef(function PasswordInput(
    { className = '', isFocused = false, ...props }: Props,
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);
    const [visible, setVisible] = useState(false);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <div className="relative">
            <input
                {...props}
                type={visible ? 'text' : 'password'}
                ref={localRef}
                className={
                    'block h-11 w-full rounded-md border border-yzh-bone-soft bg-white pl-3 pr-11 text-sm text-yzh-ink placeholder:text-yzh-text shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30 disabled:cursor-not-allowed disabled:bg-yzh-bone disabled:text-yzh-text ' +
                    className
                }
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                aria-label={visible ? 'Hide password' : 'Show password'}
                title={visible ? 'Hide password' : 'Show password'}
                className="absolute inset-y-0 right-0 flex h-11 w-11 items-center justify-center text-yzh-text transition-colors hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:rounded-md"
            >
                {visible ? (
                    <EyeOff className="h-4 w-4" />
                ) : (
                    <Eye className="h-4 w-4" />
                )}
            </button>
        </div>
    );
});
