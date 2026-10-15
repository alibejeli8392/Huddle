import { useMemo, useState } from 'react';
import { cn } from '@/utils/cn';

const sizeClass = {
    xs: 'h-6 w-6 text-[9px]',
    sm: 'h-8 w-8 text-[11px]',
    md: 'h-10 w-10 text-[13px]',
    lg: 'h-14 w-14 text-[16px]',
    xl: 'h-20 w-20 text-[22px]',
    hero: 'h-[150px] w-[150px] text-[42px]',
};

function initials(name) {
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.[0] ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';

    return (first + last).toUpperCase();
}

function imageSource(src) {
    return typeof src === 'string' && src.startsWith('http') ? src : '';
}

export default function Avatar({ src, name, size = 'md', className = '' }) {
    const [failed, setFailed] = useState(false);
    const image = imageSource(src);
    const showImage = Boolean(image) && !failed;
    const letters = useMemo(() => initials(name || ''), [name]);

    return (
        <span
            className={cn(
                'relative inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full bg-black text-white',
                sizeClass[size],
                className,
            )}
            aria-hidden="true"
        >
            {showImage ? (
                <img
                    src={image}
                    alt=""
                    className="h-full w-full object-cover"
                    onError={() => setFailed(true)}
                />
            ) : (
                <span className="font-semibold tracking-wide">{letters}</span>
            )}
        </span>
    );
}
