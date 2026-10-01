// New design wordmark ("Huddle" in Grand Hotel).
// The Breeze Laravel mark is ApplicationLogo in Components/breeze, still used by the older layouts.
import { cn } from '@/utils/cn';

const sizes = {
    sm: 'text-[26px]',
    md: 'text-[32px]',
    lg: 'text-[44px]',
    xl: 'text-[56px]',
};

export default function Logo({ className = '', size = 'md' }) {
    return (
        <span className={cn('logo-script select-none text-black', sizes[size], className)}>
            Huddle
        </span>
    );
}
