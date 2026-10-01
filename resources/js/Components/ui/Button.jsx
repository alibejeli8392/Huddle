// New design button (black / white). Social screens and Login/Register use this.
// Account settings and the older auth pages still use PrimaryButton, SecondaryButton, and DangerButton in Components/breeze.
import { cn } from '@/utils/cn';

const variants = {
    primary:
        'bg-black text-white hover:bg-neutral-800 disabled:bg-neutral-300 disabled:text-white',
    secondary:
        'bg-white text-black border border-line hover:bg-wash disabled:text-muted disabled:border-line-soft',
};

const sizes = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-9 px-4 text-sm',
    lg: 'h-11 px-5 text-sm',
};

export default function Button({
    variant = 'primary',
    size = 'md',
    block = false,
    className = '',
    children,
    disabled = false,
    type = 'button',
    ...props
}) {
    return (
        <button
            type={type}
            disabled={disabled}
            className={cn(
                'inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg font-semibold tracking-tight transition duration-150 hover:scale-[1.01] active:scale-[0.97] disabled:cursor-not-allowed disabled:hover:scale-100 disabled:active:scale-100',
                variants[variant],
                sizes[size],
                block && 'w-full',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
