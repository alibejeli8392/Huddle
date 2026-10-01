import { useState } from 'react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import Dialog from '@/Components/ui/Dialog';
import { CommentIcon, HeartIcon, ShareIcon, ThumbsDownIcon } from '@/Components/ui/Icons';
import { useHuddle } from '@/context/HuddleContext';
import { timeAgo } from '@/utils/time';

export default function PostCard({ post }) {
    const { userById, viewer, friendsOf, state, toggleLike, toggleDislike, addComment, sharePost } = useHuddle();
    const [pop, setPop] = useState(false);
    const [commentsOpen, setCommentsOpen] = useState(false);
    const [draft, setDraft] = useState('');
    const [shareOpen, setShareOpen] = useState(false);
    const [sharedTo, setSharedTo] = useState('');
    const author = userById(post.authorId);
    const liked = viewer ? post.likes.includes(viewer.id) : false;
    const disliked = viewer ? post.dislikes.includes(viewer.id) : false;
    const comments = post.comments ?? [];
    const friends = viewer ? friendsOf(viewer.id) : [];
    const groups = state.groups ?? [];

    if (!author) {
        return null;
    }

    const onLike = () => {
        toggleLike(post.id);
        setPop(true);
    };

    const sendComment = (event) => {
        event.preventDefault();
        addComment(post.id, draft);
        setDraft('');
        setCommentsOpen(true);
    };

    const sendShare = (destination, label) => {
        sharePost(post.id, destination);
        setSharedTo(label);
    };

    return (
        <article className="border-b border-line py-4">
            <header className="flex items-center gap-3 px-1">
                <Avatar src={author.avatar} name={author.name} size="sm" />
                <div className="min-w-0 flex-1">
                    <div className="flex items-baseline gap-2">
                        <span className="truncate text-sm font-semibold">{author.name}</span>
                        <span className="text-xs text-muted">{timeAgo(post.createdAt)}</span>
                    </div>
                    <p className="truncate text-xs text-muted">
                        {author.title} · {author.department}
                    </p>
                </div>
            </header>
            <p className="mt-3 px-1 text-[15px] leading-relaxed whitespace-pre-wrap">{post.text}</p>
            <div className="mt-3 flex items-center gap-4 px-1 text-sm">
                <button
                    type="button"
                    aria-label={liked ? 'Unlike' : 'Like'}
                    onClick={onLike}
                    onAnimationEnd={() => setPop(false)}
                    className={`flex items-center gap-1 ${liked ? 'text-heart' : 'text-ink'} ${pop ? 'heart-pop' : ''}`}
                >
                    <HeartIcon size={22} filled={liked} />
                    <span className="text-xs font-semibold">{post.likes.length}</span>
                </button>
                <button
                    type="button"
                    aria-label={disliked ? 'Remove dislike' : 'Dislike'}
                    onClick={() => toggleDislike(post.id)}
                    className={`flex items-center gap-1 ${disliked ? 'text-ink' : 'text-muted'}`}
                >
                    <ThumbsDownIcon size={22} filled={disliked} />
                    <span className="text-xs font-semibold">{post.dislikes.length}</span>
                </button>
                <button
                    type="button"
                    onClick={() => setCommentsOpen((open) => !open)}
                    className="flex items-center gap-1 text-ink"
                >
                    <CommentIcon size={22} />
                    <span className="text-xs font-semibold">{comments.length}</span>
                </button>
                <button
                    type="button"
                    aria-label="Share"
                    onClick={() => {
                        setSharedTo('');
                        setShareOpen(true);
                    }}
                    className="flex items-center gap-1 text-ink"
                >
                    <ShareIcon size={20} />
                </button>
            </div>

            {commentsOpen && (
                <div className="mt-3 space-y-3 px-1">
                    {comments.length === 0 ? (
                        <p className="text-sm text-muted">No comments yet.</p>
                    ) : (
                        <ul className="space-y-3">
                            {comments.map((comment) => {
                                const commenter = userById(comment.authorId);

                                return (
                                    <li key={comment.id} className="flex gap-2">
                                        <Avatar
                                            src={commenter?.avatar}
                                            name={commenter?.name ?? 'Member'}
                                            size="xs"
                                        />
                                        <div className="min-w-0">
                                            <p className="text-xs">
                                                <span className="font-semibold">{commenter?.name ?? 'Member'}</span>
                                                <span className="ml-2 text-muted">{timeAgo(comment.createdAt)}</span>
                                            </p>
                                            <p className="text-sm leading-relaxed">{comment.text}</p>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                    <form onSubmit={sendComment} className="flex items-center gap-2">
                        <input
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            placeholder="Add a comment…"
                            className="h-9 flex-1 rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                        />
                        <Button type="submit" size="sm" disabled={!draft.trim()}>
                            Post
                        </Button>
                    </form>
                </div>
            )}

            <Dialog open={shareOpen} title="Share post" onClose={() => setShareOpen(false)}>
                {sharedTo ? (
                    <p className="text-sm">Sent to {sharedTo}.</p>
                ) : (
                    <div className="space-y-4">
                        <section>
                            <h3 className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">Friends</h3>
                            {friends.length === 0 ? (
                                <p className="text-sm text-muted">No friends to message yet.</p>
                            ) : (
                                <ul className="divide-y divide-line border-t border-line">
                                    {friends.map((friend) => (
                                        <li key={friend.id}>
                                            <button
                                                type="button"
                                                onClick={() => sendShare({ kind: 'dm', id: friend.id }, friend.name)}
                                                className="flex w-full items-center gap-3 py-2 text-left hover:bg-hover"
                                            >
                                                <Avatar src={friend.avatar} name={friend.name} size="sm" />
                                                <span className="text-sm font-semibold">{friend.name}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                        <section>
                            <h3 className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">Groups</h3>
                            {groups.length === 0 ? (
                                <p className="text-sm text-muted">No groups yet.</p>
                            ) : (
                                <ul className="divide-y divide-line border-t border-line">
                                    {groups.map((group) => (
                                        <li key={group.id}>
                                            <button
                                                type="button"
                                                onClick={() => sendShare({ kind: 'group', id: group.id }, group.name)}
                                                className="w-full py-2 text-left text-sm font-semibold hover:bg-hover"
                                            >
                                                {group.name}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                )}
            </Dialog>
        </article>
    );
}
