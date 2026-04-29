import { ButtonHTMLAttributes } from 'react';

export default function DangerButton({
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
                `inline-flex h-11 items-center justify-center rounded-md bg-red-600 px-5 text-sm font-medium text-white transition-colors duration-150 ease-out hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50 ` +
                className
            }
        >
            {children}
        </button>
    );
}
