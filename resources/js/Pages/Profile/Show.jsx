import { useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import { CameraIcon } from '@/Components/ui/Icons';
import AppLayout from '@/Layouts/AppLayout';
import { useHuddle } from '@/context/HuddleContext';
import { cn } from '@/utils/cn';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

const tabs = [
    { id: 'profile', label: 'Profile', href: () => route('profile.show') },
    { id: 'account', label: 'Account settings', href: () => route('profile.show', { tab: 'account' }) },
];

export default function Show({ tab = 'profile', mustVerifyEmail, status }) {
    const { viewer, updateProfile, friendsOf, state } = useHuddle();
    const fileRef = useRef(null);
    const [name, setName] = useState(viewer?.name ?? '');
    const [title, setTitle] = useState(viewer?.title ?? '');
    const [bio, setBio] = useState(viewer?.bio ?? '');
    const [avatar, setAvatar] = useState(viewer?.avatar ?? '');
    const [saved, setSaved] = useState(false);

    if (!viewer) {
        return null;
    }

    const friendCount = friendsOf(viewer.id).length;
    const postCount = state.posts.filter((post) => post.authorId === viewer.id).length;

    const onFile = (file) => {
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                setAvatar(reader.result);
            }
        };
        reader.readAsDataURL(file);
    };

    const submit = (event) => {
        event.preventDefault();
        updateProfile({ name: name.trim(), title: title.trim(), bio, avatar });
        setSaved(true);
        window.setTimeout(() => setSaved(false), 1800);
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
                                <Avatar src={avatar} name={name || viewer.name} size="hero" />
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
                                    accept="image/*"
                                    className="hidden"
                                    onChange={(event) => onFile(event.target.files?.[0])}
                                />
                            </div>

                            <div className="w-full flex-1">
                                <h1 className="text-2xl font-light tracking-tight">{name || 'Your profile'}</h1>
                                <p className="mt-1 text-sm text-muted">{viewer.email}</p>
                                <div className="mt-4 flex gap-6 text-sm">
                                    <span>
                                        <strong className="font-semibold">{postCount}</strong>{' '}
                                        <span className="text-muted">posts</span>
                                    </span>
                                    <span>
                                        <strong className="font-semibold">{friendCount}</strong>{' '}
                                        <span className="text-muted">friends</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="mt-10 space-y-5 border-t border-line pt-8">
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Name
                                </span>
                                <input
                                    value={name}
                                    onChange={(event) => setName(event.target.value)}
                                    className="h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                                />
                            </label>
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    Title
                                </span>
                                <input
                                    value={title}
                                    onChange={(event) => setTitle(event.target.value)}
                                    className="h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                                />
                            </label>
                            <label className="block">
                                <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                                    About me
                                </span>
                                <textarea
                                    value={bio}
                                    onChange={(event) => setBio(event.target.value)}
                                    rows={5}
                                    maxLength={280}
                                    placeholder="A short bio your teammates will see."
                                    className="w-full rounded-lg border border-line bg-wash px-3 py-2.5 text-sm leading-relaxed shadow-none focus:border-black focus:ring-black"
                                />
                                <span className="mt-1 block text-right text-[11px] text-muted">{bio.length}/280</span>
                            </label>
                            <div className="flex items-center gap-3">
                                <Button type="submit">Save</Button>
                                {saved && <span className="text-sm font-medium">Saved</span>}
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}

Show.layout = (page) => <AppLayout>{page}</AppLayout>;
