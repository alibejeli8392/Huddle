import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import { CameraIcon } from '@/Components/ui/Icons';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/utils/cn';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

const tabs = [
    { id: 'profile', label: 'Profile', href: () => route('profile.show') },
    { id: 'account', label: 'Account settings', href: () => route('profile.show', { tab: 'account' }) },
];

export default function Show({ tab = 'profile', mustVerifyEmail, status, profile }) {
    const { errors } = usePage().props;
    const fileRef = useRef(null);
    const [title, setTitle] = useState(profile?.title ?? '');
    const [department, setDepartment] = useState(profile?.department ?? '');
    const [bio, setBio] = useState(profile?.bio ?? '');

    useEffect(() => {
        setTitle(profile?.title ?? '');
        setDepartment(profile?.department ?? '');
        setBio(profile?.bio ?? '');
    }, [profile?.title, profile?.department, profile?.bio]);

    if (!profile) {
        return null;
    }

    const submit = (event) => {
        event.preventDefault();
        router.patch(
            route('profile.details.update'),
            { title, department, bio },
            { preserveScroll: true },
        );
    };

    const uploadAvatar = (file) => {
        if (!file) {
            return;
        }

        router.post(
            route('profile.avatar.store'),
            { avatar: file },
            { preserveScroll: true, forceFormData: true },
        );
    };

    const removeAvatar = () => {
        router.delete(route('profile.avatar.destroy'), { preserveScroll: true });
    };

    return (
        <>
            <Head title={tab === 'account' ? 'Account settings' : 'Profile'} />

            <div className="mx-auto w-full max-w-[720px] px-4 py-8 lg:py-12">
                <div className="mb-8 flex gap-6 border-b border-line">
                    {tabs.map((item) => (
                        <Link
                            key={item.id}
                            href={item.href()}
                            preserveScroll
                            preserveState
                            aria-current={tab === item.id ? 'page' : undefined}
                            className={cn(
                                '-mb-px border-b-2 px-1 pb-3 text-sm',
                                tab === item.id
                                    ? 'border-black font-semibold text-ink'
                                    : 'border-transparent text-muted hover:text-ink',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>

                {tab === 'account' ? (
                    <div className="space-y-6">
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                className="max-w-xl"
                            />
                        </div>

                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>

                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <DeleteUserForm className="max-w-xl" />
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submit}>
                        <div className="flex flex-col items-center gap-8 sm:flex-row sm:items-start">
                            <div className="relative shrink-0">
                                <Avatar src={profile.avatar} name={profile.name} size="hero" />
                                <button
                                    type="button"
                                    onClick={() => fileRef.current?.click()}
                                    className="absolute right-1 bottom-1 flex h-10 w-10 items-center justify-center rounded-full border border-line bg-white text-ink shadow-sm hover:bg-wash"
                                    aria-label="Change photo"
                                >
                                    <CameraIcon size={18} />
                                </button>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    className="hidden"
                                    onChange={(event) => uploadAvatar(event.target.files?.[0])}
                                />
                            </div>

                            <div className="w-full flex-1">
                                <h1 className="text-2xl font-light tracking-tight">{profile.name}</h1>
                                <p className="mt-1 text-sm text-muted">{profile.email}</p>
                                <div className="mt-4 flex gap-6 text-sm">
                                    <span>
                                        <strong className="font-semibold">{profile.postCount}</strong>{' '}
                                        <span className="text-muted">posts</span>
                                    </span>
                                    <span>
                                        <strong className="font-semibold">{profile.friendCount}</strong>{' '}
                                        <span className="text-muted">friends</span>
                                    </span>
                                </div>
                                {profile.avatar && (
                                    <button
                                        type="button"
                                        onClick={removeAvatar}
                                        className="mt-3 text-xs font-semibold text-muted"
                                    >
                                        Remove photo
                                    </button>
                                )}
                                {errors?.avatar && <p className="mt-2 text-sm text-heart">{errors.avatar}</p>}
                            </div>
                        </div>

                        <div className="mt-10 space-y-5 border-t border-line pt-8">
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Title
                                </span>
                                <input
                                    value={title}
                                    onChange={(event) => setTitle(event.target.value)}
                                    className="h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                                />
                                {errors?.title && <p className="mt-1 text-sm text-heart">{errors.title}</p>}
                            </label>
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Department
                                </span>
                                <input
                                    value={department}
                                    onChange={(event) => setDepartment(event.target.value)}
                                    className="h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                                />
                                {errors?.department && <p className="mt-1 text-sm text-heart">{errors.department}</p>}
                            </label>
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    About me
                                </span>
                                <textarea
                                    value={bio}
                                    onChange={(event) => setBio(event.target.value)}
                                    rows={5}
                                    maxLength={500}
                                    placeholder="A short bio your teammates will see."
                                    className="w-full rounded-lg border border-line bg-wash px-3 py-2.5 text-sm leading-relaxed shadow-none focus:border-black focus:ring-black"
                                />
                                <span className="mt-1 block text-right text-[11px] text-muted">{bio.length}/500</span>
                                {errors?.bio && <p className="mt-1 text-sm text-heart">{errors.bio}</p>}
                            </label>
                            <div className="flex items-center gap-3">
                                <Button type="submit">Save</Button>
                                {status === 'profile-updated' && <span className="text-sm font-medium">Saved</span>}
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}

Show.layout = (page) => <AppLayout>{page}</AppLayout>;
