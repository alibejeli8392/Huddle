import { Link } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import {
    CompassIcon,
    HomeIcon,
    LogoutIcon,
    PeopleIcon,
    PlaneIcon,
} from '@/Components/ui/Icons';
import Logo from '@/Components/ui/Logo';
import { useHuddle } from '@/context/HuddleContext';
import { cn } from '@/utils/cn';

const links = [
    { href: () => route('dashboard'), label: 'Home', icon: HomeIcon, name: 'dashboard' },
    { href: () => route('members.index'), label: 'Members', icon: CompassIcon, name: 'members.index' },
    { href: () => route('messages.index'), label: 'Messages', icon: PlaneIcon, name: 'messages.index' },
    { href: () => route('friends.index'), label: 'Friends', icon: PeopleIcon, name: 'friends.index' },
];

function isCurrent(name) {
    return route().current(name);
}

export default function Navbar() {
    const { viewer, incomingRequests } = useHuddle();
    const requestCount = incomingRequests.length;
    const profileHref = route('profile.show');

    return (
        <>
            <aside className="fixed top-0 left-0 z-40 hidden h-screen w-[244px] flex-col border-r border-line bg-white px-3 py-5 lg:flex">
                <Link href={route('dashboard')} className="mb-8 px-3 pt-2">
                    <Logo size="lg" />
                </Link>
                <nav className="flex flex-1 flex-col gap-1">
                    {links.map((link) => {
                        const href = link.href();
                        const active = isCurrent(link.name);
                        const Icon = link.icon;

                        return (
                            <Link
                                key={link.name}
                                href={href}
                                prefetch
                                className={cn(
                                    'group relative flex items-center gap-4 rounded-lg px-3 py-3 text-[16px] text-ink transition-colors hover:bg-hover',
                                    active ? 'font-bold' : 'font-normal',
                                )}
                            >
                                <span className="relative">
                                    <Icon size={24} strokeWidth={active ? 2.2 : 1.7} />
                                    {link.name === 'friends.index' && requestCount > 0 && (
                                        <span className="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-heart" />
                                    )}
                                </span>
                                {link.label}
                            </Link>
                        );
                    })}
                    <Link
                        href={profileHref}
                        prefetch
                        className={cn(
                            'flex items-center gap-4 rounded-lg px-3 py-3 text-[16px] text-ink transition-colors hover:bg-hover',
                            route().current('profile.show') ? 'font-bold' : 'font-normal',
                        )}
                    >
                        <Avatar src={viewer?.avatar} name={viewer?.name ?? 'You'} size="xs" />
                        Profile
                    </Link>
                </nav>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="flex items-center gap-4 rounded-lg px-3 py-3 text-[16px] text-ink transition-colors hover:bg-hover"
                >
                    <LogoutIcon size={24} />
                    Log out
                </Link>
            </aside>

            <header className="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-line bg-white px-4 lg:hidden">
                <Link href={route('dashboard')}>
                    <Logo size="sm" />
                </Link>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="flex h-9 w-9 items-center justify-center rounded-full hover:bg-hover"
                    aria-label="Log out"
                >
                    <LogoutIcon size={22} />
                </Link>
            </header>

            <nav className="fixed right-0 bottom-0 left-0 z-40 flex h-14 items-center justify-around border-t border-line bg-white lg:hidden">
                {links.map((link) => {
                    const active = isCurrent(link.name);
                    const Icon = link.icon;

                    return (
                        <Link
                            key={link.name}
                            href={link.href()}
                            className="relative flex h-full flex-1 items-center justify-center text-ink"
                        >
                            <Icon size={24} strokeWidth={active ? 2.2 : 1.7} />
                            {link.name === 'friends.index' && requestCount > 0 && (
                                <span className="absolute top-2 right-[calc(50%-14px)] h-2 w-2 rounded-full bg-heart" />
                            )}
                        </Link>
                    );
                })}
                <Link href={profileHref} className="flex h-full flex-1 items-center justify-center">
                    <Avatar
                        src={viewer?.avatar}
                        name={viewer?.name ?? 'You'}
                        size="xs"
                        className={route().current('profile.show') ? 'ring-2 ring-black ring-offset-1' : ''}
                    />
                </Link>
            </nav>
        </>
    );
}
