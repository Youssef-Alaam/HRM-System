import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'h-4 w-4 rounded border-yzh-bone-soft text-yzh-gold shadow-sm focus:ring-yzh-gold focus:ring-offset-0 ' +
                className
            }
        />
    );
}
