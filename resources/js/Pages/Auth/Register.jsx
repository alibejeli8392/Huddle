import AuthFrame from '@/Components/ui/AuthFrame';
import Button from '@/Components/ui/Button';
import InputError from '@/Components/breeze/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

const fieldClass =
    'h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm text-ink shadow-none focus:border-black focus:ring-black';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthFrame
            footer={
                <>
                    Have an account?{' '}
                    <Link href={route('login')} className="font-semibold text-ink">
                        Log in
                    </Link>
                </>
            }
        >
            <Head title="Register" />

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="name" className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Name
                        </span>
                        <input
                            id="name"
                            name="name"
                            value={data.name}
                            className={fieldClass}
                            autoComplete="name"
                            autoFocus
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                    </label>
                    <InputError message={errors.name} className="mt-2" />
                </div>

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
                            onChange={(e) => setData('email', e.target.value)}
                            required
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
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                    </label>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Confirm Password
                        </span>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className={fieldClass}
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                    </label>
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <Button type="submit" size="lg" block disabled={processing}>
                    Register
                </Button>
            </form>
        </AuthFrame>
    );
}
