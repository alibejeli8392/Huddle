import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import Dialog from '@/Components/ui/Dialog';
import { PlusIcon, SendIcon } from '@/Components/ui/Icons';
import AppLayout from '@/Layouts/AppLayout';
import { timeAgo } from '@/utils/time';
import { cn } from '@/utils/cn';

function SharedPost({ sharedPost }) {
    if (!sharedPost?.author) {
        return null;
    }

    return (
        <Link
            href={route('members.show', sharedPost.author.id)}
            className="mt-1 block rounded-xl border border-line bg-white px-3 py-2 text-left text-ink"
        >
            <p className="text-[11px] font-semibold">{sharedPost.author.name}</p>
            <p className="mt-1 text-sm leading-relaxed">{sharedPost.excerpt}</p>
        </Link>
    );
}

function MemberAvatars({ members }) {
    const shown = members.slice(0, 3);

    if (shown.length <= 1) {
        const person = shown[0];

        return <Avatar src={person?.avatar} name={person?.name ?? 'Chat'} size="md" />;
    }

    return (
        <span className="flex -space-x-2">
            {shown.map((person) => (
                <Avatar
                    key={person.id}
                    src={person.avatar}
                    name={person.name}
                    size="sm"
                    className="ring-2 ring-white"
                />
            ))}
        </span>
    );
}

export default function Index({ conversations = [], activeConversation = null, friends = [] }) {
    const { auth, errors } = usePage().props;
    const viewerId = auth?.user?.id;
    const [text, setText] = useState('');
    const [creating, setCreating] = useState(false);
    const [picking, setPicking] = useState(false);
    const [groupName, setGroupName] = useState('');
    const [picked, setPicked] = useState([]);
    const [sending, setSending] = useState(false);
    const bottomRef = useRef(null);

    const messages = activeConversation?.messages ?? [];

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [activeConversation?.id, messages.length]);

    const otherMembers = (activeConversation?.members ?? []).filter((member) => member.id !== viewerId);
    const directPeer = activeConversation?.type === 'direct' ? otherMembers[0] : null;

    const openConversation = (id) => {
        setText('');
        router.get(route('messages.show', id), {}, { preserveScroll: true });
    };

    const toggleFriend = (id) => {
        setPicked((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const startDirect = (friendId) => {
        router.post(route('conversations.store'), {
            type: 'direct',
            friend_id: friendId,
        });
    };

    const saveGroup = (event) => {
        event.preventDefault();

        if (!groupName.trim() || picked.length < 2 || sending) {
            return;
        }

        router.post(
            route('conversations.store'),
            {
                type: 'group',
                name: groupName,
                member_ids: picked,
            },
            {
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
                onSuccess: () => {
                    setCreating(false);
                    setGroupName('');
                    setPicked([]);
                },
            },
        );
    };

    const canSend = Boolean(text.trim() && activeConversation && !sending);

    const submit = (event) => {
        event.preventDefault();

        if (!canSend) {
            return;
        }

        router.post(
            route('conversations.messages.store', activeConversation.id),
            { body: text },
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
                onSuccess: () => setText(''),
            },
        );
    };

    return (
        <>
            <Head title="Messages" />

            <div className="mx-auto flex h-[calc(100dvh-7.5rem)] max-w-[1100px] overflow-hidden lg:h-screen">
                <aside className="flex w-[168px] min-h-0 shrink-0 flex-col border-r border-line sm:w-[300px]">
                    <div className="border-b border-line px-3 py-3">
                        <h1 className="truncate text-base font-semibold">Messages</h1>
                        <div className="mt-3 flex flex-col gap-2">
                            <Button type="button" size="sm" className="w-full" onClick={() => setCreating(true)}>
                                <PlusIcon size={14} />
                                New group
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                className="w-full"
                                onClick={() => setPicking(true)}
                            >
                                New message
                            </Button>
                        </div>
                    </div>
                    <div className="ig-scroll flex-1 overflow-y-auto">
                        {conversations.length === 0 && (
                            <p className="px-3 py-6 text-sm text-muted">No conversations yet.</p>
                        )}
                        {conversations.map((conversation) => {
                            const people =
                                conversation.type === 'direct'
                                    ? conversation.members.filter((member) => member.id !== viewerId)
                                    : conversation.members;

                            return (
                                <button
                                    key={conversation.id}
                                    type="button"
                                    onClick={() => openConversation(conversation.id)}
                                    className={cn(
                                        'flex w-full items-center gap-3 px-3 py-3 text-left hover:bg-hover',
                                        activeConversation?.id === conversation.id && 'bg-wash',
                                    )}
                                >
                                    <span className="relative shrink-0">
                                        <MemberAvatars members={people.length > 0 ? people : conversation.members} />
                                        {conversation.unreadCount > 0 && (
                                            <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-heart px-1 text-[10px] font-semibold leading-none text-white">
                                                {conversation.unreadCount}
                                            </span>
                                        )}
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm font-semibold">{conversation.name}</span>
                                        <span className="block truncate text-xs text-muted">
                                            {conversation.preview || 'No messages yet'}
                                            {conversation.previewAt ? ` · ${timeAgo(conversation.previewAt)}` : ''}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </aside>

                <section className="flex min-h-0 min-w-0 flex-1 flex-col">
                    <header className="flex h-14 items-center gap-3 border-b border-line px-4">
                        {activeConversation?.type === 'group' ? (
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold">{activeConversation.name}</p>
                                <p className="truncate text-xs text-muted">
                                    {activeConversation.members
                                        .map((member) => (member.id === viewerId ? 'You' : member.name))
                                        .join(', ')}
                                </p>
                            </div>
                        ) : directPeer ? (
                            <>
                                <Avatar src={directPeer.avatar} name={directPeer.name} size="sm" />
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold">{directPeer.name}</p>
                                    <p className="truncate text-xs text-muted">{directPeer.title}</p>
                                </div>
                            </>
                        ) : (
                            <p className="text-sm text-muted">Choose a conversation</p>
                        )}
                    </header>

                    <div className="ig-scroll flex-1 space-y-4 overflow-y-auto px-4 py-5">
                        {!activeConversation && (
                            <p className="py-16 text-center text-sm text-muted">Pick a conversation from the left.</p>
                        )}

                        {activeConversation && messages.length === 0 && (
                            <p className="py-16 text-center text-sm text-muted">No messages yet. Say hello.</p>
                        )}

                        {messages.map((message) => {
                            const mine = message.sender?.id === viewerId;
                            const author = message.sender;

                            if (!author) {
                                return null;
                            }

                            return (
                                <div key={message.id} className={cn('flex gap-2', mine ? 'justify-end' : 'justify-start')}>
                                    {!mine && <Avatar src={author.avatar} name={author.name} size="xs" />}
                                    <div className={cn('max-w-[75%]', mine && 'text-right')}>
                                        {activeConversation.type === 'group' && (
                                            <p className="mb-1 text-[11px] font-semibold text-muted">
                                                {mine ? 'You' : author.name} · {timeAgo(message.createdAt)}
                                            </p>
                                        )}
                                        <div
                                            className={cn(
                                                'inline-block rounded-2xl px-3.5 py-2 text-left text-sm leading-relaxed',
                                                mine ? 'bg-black text-white' : 'bg-line-soft text-ink',
                                            )}
                                        >
                                            {message.body}
                                            <SharedPost sharedPost={message.sharedPost} />
                                        </div>
                                        {activeConversation.type !== 'group' && (
                                            <p className="mt-1 text-[11px] text-muted">{timeAgo(message.createdAt)}</p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                        <div ref={bottomRef} />
                    </div>

                    <form onSubmit={submit} className="border-t border-line px-4 py-3">
                        <div className="flex items-end gap-2 rounded-full border border-line px-3 py-1.5">
                            <textarea
                                value={text}
                                onChange={(event) => setText(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' && !event.shiftKey) {
                                        event.preventDefault();
                                        submit(event);
                                    }
                                }}
                                rows={1}
                                maxLength={1000}
                                placeholder={
                                    activeConversation
                                        ? `Message ${activeConversation.name}…`
                                        : 'Select a conversation…'
                                }
                                className="max-h-28 min-h-[36px] flex-1 border-0 bg-transparent py-2 text-sm shadow-none focus:outline-none focus:ring-0"
                            />
                            <Button type="submit" size="sm" disabled={!canSend} className="mb-0.5 rounded-full px-3">
                                <SendIcon size={16} />
                                Send
                            </Button>
                        </div>
                        {errors?.body && <p className="mt-2 text-sm text-heart">{errors.body}</p>}
                    </form>
                </section>
            </div>

            <Dialog
                open={picking}
                title="New message"
                onClose={() => setPicking(false)}
            >
                {friends.length === 0 ? (
                    <p className="text-sm text-muted">Add a friend before starting a chat.</p>
                ) : (
                    <ul className="max-h-72 divide-y divide-line overflow-y-auto border-t border-line">
                        {friends.map((friend) => (
                            <li key={friend.id}>
                                <button
                                    type="button"
                                    onClick={() => startDirect(friend.id)}
                                    className="flex w-full items-center gap-3 py-2 text-left hover:bg-hover"
                                >
                                    <Avatar src={friend.avatar} name={friend.name} size="sm" />
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm font-semibold">{friend.name}</span>
                                        {friend.title && (
                                            <span className="block truncate text-xs text-muted">{friend.title}</span>
                                        )}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                {errors?.friend_id && <p className="mt-3 text-sm text-heart">{errors.friend_id}</p>}
            </Dialog>

            <Dialog open={creating} title="Create group" onClose={() => setCreating(false)}>
                <form onSubmit={saveGroup} className="space-y-4">
                    <label className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Name
                        </span>
                        <input
                            value={groupName}
                            onChange={(event) => setGroupName(event.target.value)}
                            maxLength={60}
                            className="h-11 w-full rounded-lg border border-line bg-wash px-3 text-sm shadow-none focus:border-black focus:ring-black"
                            placeholder="Launch crew"
                        />
                    </label>
                    <fieldset>
                        <legend className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">
                            Friends
                        </legend>
                        {friends.length === 0 ? (
                            <p className="text-sm text-muted">Add a friend before starting a group.</p>
                        ) : (
                            <ul className="max-h-56 space-y-2 overflow-y-auto">
                                {friends.map((friend) => (
                                    <li key={friend.id}>
                                        <label className="flex items-center gap-3 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={picked.includes(friend.id)}
                                                onChange={() => toggleFriend(friend.id)}
                                                className="rounded border-line text-black shadow-none focus:ring-black"
                                            />
                                            <Avatar src={friend.avatar} name={friend.name} size="xs" />
                                            {friend.name}
                                        </label>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </fieldset>
                    {picked.length === 1 && (
                        <p className="text-sm text-muted">Choose at least two friends.</p>
                    )}
                    {(errors?.name || errors?.member_ids) && (
                        <p className="text-sm text-heart">{errors.name || errors.member_ids}</p>
                    )}
                    <Button type="submit" block disabled={!groupName.trim() || picked.length < 2 || sending}>
                        Create group
                    </Button>
                </form>
            </Dialog>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
