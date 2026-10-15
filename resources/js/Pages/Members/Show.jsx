import { Head, Link } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import FriendButton from '@/Components/social/FriendButton';
import PostCard from '@/Components/social/PostCard';
import AppLayout from '@/Layouts/AppLayout';

export default function Show({ member, posts = [] }) {
    return (
        <>
            <Head title={member.name} />

            <div className="mx-auto w-full max-w-[720px] px-4 py-8 lg:py-12">
                <div className="flex flex-col items-center gap-8 sm:flex-row sm:items-start">
                    <Avatar src={member.avatar} name={member.name} size="hero" />
                    <div className="w-full flex-1">
                        <h1 className="text-2xl font-light tracking-tight">{member.name}</h1>
                        <p className="mt-1 text-sm text-muted">
                            {[member.title, member.department].filter(Boolean).join(' · ') || 'Teammate'}
                        </p>
                        {member.bio && <p className="mt-4 text-sm leading-relaxed whitespace-pre-wrap">{member.bio}</p>}
                        <div className="mt-4 max-w-xs">
                            <FriendButton user={member} />
                        </div>
                    </div>
                </div>

                <div className="mt-10 border-t border-line pt-6">
                    <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted uppercase">Posts</h2>
                    {posts.length === 0 ? (
                        <p className="py-6 text-sm text-muted">No posts yet.</p>
                    ) : (
                        posts.map((post) => <PostCard key={post.id} post={post} />)
                    )}
                </div>

                <p className="mt-6 text-sm">
                    <Link href={route('members.index')} className="font-semibold underline">
                        Back to People
                    </Link>
                </p>
            </div>
        </>
    );
}

Show.layout = (page) => <AppLayout>{page}</AppLayout>;
