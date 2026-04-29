import { ButtonHTMLAttributes } from 'react';

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            type={type}
            disabled={disabled}
            className={
                `inline-flex h-11 items-center justify-center rounded-md border border-yzh-bone-soft bg-white px-5 text-sm font-medium text-yzh-slate transition-colors duration-150 ease-out hover:border-yzh-slate hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ` +
                className
            }
        >
            {children}
        </button>
    );
}
