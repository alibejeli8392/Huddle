import Logo from '@/Components/ui/Logo';

export default function AuthFrame({ children, footer }) {
    return (
        <div className="huddle-app flex min-h-screen items-center justify-center bg-white px-4 py-10">
            <div className="w-full max-w-[360px]">
                <div className="border border-line bg-white px-8 pt-10 pb-8">
                    <div className="mb-8 flex justify-center">
                        <Logo size="xl" />
                    </div>
                    {children}
                </div>
                {footer && (
                    <div className="mt-3 border border-line bg-white px-4 py-4 text-center text-sm text-ink">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}
