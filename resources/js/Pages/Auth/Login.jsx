import AuthFrame from '@/Components/ui/AuthFrame';
import Button from '@/Components/ui/Button';
import InputError from '@/Components/breeze/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

const fieldClass =
    'h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm text-ink shadow-none focus:border-black focus:ring-black';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthFrame
            footer={
                <>
                    Don&apos;t have an account?{' '}
                    <Link href={route('register')} className="font-semibold text-ink">
                        Sign up
                    </Link>
                </>
            }
        >
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-sm font-medium text-ink">{status}</div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Email
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className={fieldClass}
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </label>
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <label htmlFor="password" className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Password
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className={fieldClass}
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </label>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="rounded border-line text-black shadow-none focus:ring-black"
                        />
                        Remember me
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-xs font-semibold text-ink"
                        >
                            Forgot your password?
                        </Link>
                    )}
                </div>

                <Button type="submit" size="lg" block disabled={processing}>
                    Log in
                </Button>
            </form>

            <div className="my-4 flex items-center gap-3">
                <span className="h-px flex-1 bg-line" />
                <span className="text-[11px] font-semibold tracking-wide text-muted uppercase">or</span>
                <span className="h-px flex-1 bg-line" />
            </div>

            <Link
                href={route('guest.login')}
                method="post"
                as="button"
                className="inline-flex h-11 w-full items-center justify-center rounded-lg border border-line bg-white text-sm font-semibold text-black hover:bg-wash"
            >
                Continue as Guest
            </Link>
        </AuthFrame>
    );
}
