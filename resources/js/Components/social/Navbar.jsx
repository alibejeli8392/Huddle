import { Link, usePage } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import {
    ChecklistIcon,
    CompassIcon,
    HomeIcon,
    LogoutIcon,
    PeopleIcon,
    PlaneIcon,
} from '@/Components/ui/Icons';
import Logo from '@/Components/ui/Logo';
import { cn } from '@/utils/cn';

const links = [
    { href: () => route('dashboard'), label: 'Home', icon: HomeIcon, name: 'dashboard' },
    { href: () => route('members.index'), label: 'People', icon: CompassIcon, name: 'members.index' },
    { href: () => route('friends.index'), label: 'Friends', icon: PeopleIcon, name: 'friends.index', badge: 'friends' },
    { href: () => route('messages.index'), label: 'Messages', icon: PlaneIcon, name: 'messages.index', badge: 'messages' },
    { href: () => route('tasks.index'), label: 'Tasks', icon: ChecklistIcon, name: 'tasks.index', badge: 'tasks' },
];

function isCurrent(name) {
    if (name === 'messages.index') {
        return route().current('messages.index') || route().current('messages.show');
    }

    return route().current(name);
}

function badgeCount(kind, requestCount, unreadMessageCount, incompleteTaskCount) {
    if (kind === 'friends') {
        return requestCount;
    }

    if (kind === 'messages') {
        return unreadMessageCount;
    }

    if (kind === 'tasks') {
        return incompleteTaskCount;
    }

    return 0;
}

function accessibleName(label, count, kind) {
    if (!count) {
        return undefined;
    }

    if (kind === 'friends') {
        return `${label}, ${count} pending ${count === 1 ? 'request' : 'requests'}`;
    }

    if (kind === 'tasks') {
        return `${label}, ${count} incomplete ${count === 1 ? 'task' : 'tasks'}`;
    }

    return `${label}, ${count} unread ${count === 1 ? 'message' : 'messages'}`;
}

function CountBadge({ count }) {
    if (!count) {
        return null;
    }

    return (
        <span className="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-heart px-1 text-[10px] font-semibold leading-none text-white">
            {count > 99 ? '99+' : count}
        </span>
    );
}

function desktopItemClass(active) {
    return cn(
        'flex items-center gap-4 rounded-lg px-3 py-3 text-[16px] transition-colors',
        active ? 'bg-black font-semibold text-white' : 'font-normal text-ink hover:bg-hover',
    );
}

function mobileItemClass(active) {
    return cn(
        'relative flex h-full min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-0.5 text-[10px] leading-none',
        active ? 'font-semibold text-ink' : 'font-normal text-muted',
    );
}

export default function Navbar() {
    const { auth, friendRequestCount = 0, unreadMessageCount = 0, incompleteTaskCount = 0 } = usePage().props;
    const viewer = auth?.user;
    const requestCount = friendRequestCount;
    const profileHref = route('profile.show');
    const profileActive = route().current('profile.show');

    return (
        <>
            <aside className="fixed top-0 left-0 z-40 hidden h-screen w-[244px] flex-col border-r border-line bg-white px-3 py-5 lg:flex">
                <Link href={route('dashboard')} className="mb-8 px-3 pt-2">
                    <Logo size="lg" />
                </Link>
                <nav className="flex flex-1 flex-col gap-1" aria-label="Primary">
                    {links.map((link) => {
                        const active = isCurrent(link.name);
                        const Icon = link.icon;
                        const count = badgeCount(link.badge, requestCount, unreadMessageCount, incompleteTaskCount);

                        return (
                            <Link
                                key={link.name}
                                href={link.href()}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                aria-label={accessibleName(link.label, count, link.badge)}
                                className={desktopItemClass(active)}
                            >
                                <span className="relative">
                                    <Icon size={24} strokeWidth={active ? 2.2 : 1.7} />
                                    <CountBadge count={count} />
                                </span>
                                {link.label}
                            </Link>
                        );
                    })}
                    <Link
                        href={profileHref}
                        prefetch
                        aria-current={profileActive ? 'page' : undefined}
                        className={desktopItemClass(profileActive)}
                    >
                        <Avatar
                            src={viewer?.avatar_url}
                            name={viewer?.name ?? 'You'}
                            size="xs"
                            className={profileActive ? 'ring-2 ring-white' : ''}
                        />
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

            <nav
                className="fixed right-0 bottom-0 left-0 z-40 flex h-16 items-stretch justify-around border-t border-line bg-white lg:hidden"
                aria-label="Primary"
            >
                {links.map((link) => {
                    const active = isCurrent(link.name);
                    const Icon = link.icon;
                    const count = badgeCount(link.badge, requestCount, unreadMessageCount, incompleteTaskCount);

                    return (
                        <Link
                            key={link.name}
                            href={link.href()}
                            aria-current={active ? 'page' : undefined}
                            aria-label={accessibleName(link.label, count, link.badge)}
                            className={mobileItemClass(active)}
                        >
                            {active && <span className="absolute top-0 left-1/2 h-0.5 w-8 -translate-x-1/2 bg-black" />}
                            <span className="relative">
                                <Icon size={20} strokeWidth={active ? 2.2 : 1.7} />
                                <CountBadge count={count} />
                            </span>
                            <span className="max-w-full truncate">{link.label}</span>
                        </Link>
                    );
                })}
                <Link
                    href={profileHref}
                    aria-current={profileActive ? 'page' : undefined}
                    className={mobileItemClass(profileActive)}
                >
                    {profileActive && <span className="absolute top-0 left-1/2 h-0.5 w-8 -translate-x-1/2 bg-black" />}
                    <Avatar
                        src={viewer?.avatar_url}
                        name={viewer?.name ?? 'You'}
                        size="xs"
                        className={profileActive ? 'ring-2 ring-black ring-offset-1' : ''}
                    />
                    <span className="max-w-full truncate">Profile</span>
                </Link>
            </nav>
        </>
    );
}
