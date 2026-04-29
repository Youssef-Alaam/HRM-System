import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            ref={localRef}
            className={
                'block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink placeholder:text-yzh-text shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30 disabled:cursor-not-allowed disabled:bg-yzh-bone disabled:text-yzh-text ' +
                className
            }
        />
    );
});
