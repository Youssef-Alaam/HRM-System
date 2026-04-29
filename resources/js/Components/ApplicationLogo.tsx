import { ImgHTMLAttributes } from 'react';

export default function ApplicationLogo({
    className = '',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/yzh-mark.png"
            alt="YZH"
            className={'h-8 w-auto ' + className}
            {...props}
        />
    );
}
