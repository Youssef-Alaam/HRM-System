import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            disabled={disabled}
            className={
                `inline-flex h-11 items-center justify-center rounded-md bg-yzh-gold px-5 text-sm font-medium text-yzh-ink transition-colors duration-150 ease-out hover:bg-yzh-gold-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone active:bg-yzh-gold-600 disabled:cursor-not-allowed disabled:opacity-50 ` +
                className
            }
        >
            {children}
        </button>
    );
}
