import { Link } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import FriendButton from '@/Components/social/FriendButton';

export default function MemberCard({ user }) {
    const profileHref = route('members.show', user.id);

    return (
        <article className="group flex flex-col overflow-hidden bg-white">
            <Link href={profileHref} className="relative aspect-square overflow-hidden bg-wash">
                {user.avatar ? (
                    <img
                        src={user.avatar}
                        alt=""
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-black">
                        <Avatar src={user.avatar} name={user.name} size="xl" />
                    </div>
                )}
            </Link>
            <div className="flex flex-col gap-3 border-x border-b border-line px-3 py-3">
                <Link href={profileHref} className="flex items-center gap-2">
                    <Avatar src={user.avatar} name={user.name} size="xs" />
                    <div className="min-w-0">
                        <h3 className="truncate text-sm font-semibold">{user.name}</h3>
                        <p className="truncate text-[11px] text-muted">{user.title}</p>
                    </div>
                </Link>
                <FriendButton user={user} block />
            </div>
        </article>
    );
}

