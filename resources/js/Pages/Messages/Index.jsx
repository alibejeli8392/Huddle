import { useEffect, useMemo, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import Avatar from '@/Components/ui/Avatar';
import Button from '@/Components/ui/Button';
import Dialog from '@/Components/ui/Dialog';
import { SendIcon } from '@/Components/ui/Icons';
import AppLayout from '@/Layouts/AppLayout';
import { useHuddle } from '@/context/HuddleContext';
import { timeAgo } from '@/utils/time';
import { cn } from '@/utils/cn';

function SharedPost({ sharedPost }) {
    if (!sharedPost) {
        return null;
    }

    return (
        <div className="mt-1 rounded-xl border border-line bg-white px-3 py-2 text-left text-ink">
            <p className="text-[11px] font-semibold">Shared post · {sharedPost.authorName}</p>
            <p className="mt-1 text-sm leading-relaxed">{sharedPost.text}</p>
        </div>
    );
}

export default function Index() {
    const {
        viewer,
        state,
        userById,
        friendsOf,
        sendPrivateMessage,
        sendGroupMessage,
        createGroup,
        threadWith,
        markDirectRead,
        markGroupRead,
    } = useHuddle();
    const [channel, setChannel] = useState(null);
    const [peerId, setPeerId] = useState(null);
    const [groupId, setGroupId] = useState(null);
    const [text, setText] = useState('');
    const [creating, setCreating] = useState(false);
    const [groupName, setGroupName] = useState('');
    const [picked, setPicked] = useState([]);
    const bottomRef = useRef(null);

    const friends = viewer ? friendsOf(viewer.id) : [];
    const groups = state.groups ?? [];

    const people = useMemo(() => {
        const map = new Map();

        friends.forEach((friend) => map.set(friend.id, friend));

        state.messages.forEach((message) => {
            const other =
                message.fromId === viewer?.id
                    ? userById(message.toId)
                    : message.toId === viewer?.id
                      ? userById(message.fromId)
                      : undefined;

            if (other) {
                map.set(other.id, other);
            }
        });

        return [...map.values()];
    }, [friends, state.messages, viewer, userById]);

    const privateMessages = peerId ? threadWith(peerId) : [];
    const peer = peerId ? userById(peerId) : undefined;
    const group = groups.find((item) => item.id === groupId);
    const groupMessages = (state.groupMessages ?? [])
        .filter((message) => message.groupId === groupId)
        .sort((a, b) => a.createdAt - b.createdAt);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [channel, peerId, groupId, privateMessages.length, groupMessages.length]);

    useEffect(() => {
        if (channel === 'dm' && peerId) {
            markDirectRead(peerId);
        }
    }, [channel, peerId, markDirectRead]);

    useEffect(() => {
        if (channel === 'group' && groupId) {
            markGroupRead(groupId);
        }
    }, [channel, groupId, markGroupRead]);

    const unreadWith = (userId) =>
        state.messages.filter(
            (message) => message.unread && message.fromId === userId && message.toId === viewer?.id,
        ).length;

    const unreadInGroup = (id) =>
        (state.groupMessages ?? []).filter(
            (message) => message.unread && message.groupId === id && message.fromId !== viewer?.id,
        ).length;

    if (!viewer) {
        return null;
    }

    const canSend =
        channel === 'dm'
            ? Boolean(text.trim() && peerId)
            : channel === 'group' && Boolean(text.trim() && groupId);

    const submit = (event) => {
        event.preventDefault();

        if (!canSend) {
            return;
        }

        if (channel === 'dm' && peerId) {
            sendPrivateMessage(peerId, text);
        } else if (channel === 'group' && groupId) {
            sendGroupMessage(groupId, text);
        }

        setText('');
    };

    const selectPeer = (id) => {
        setChannel('dm');
        setPeerId(id);
        setGroupId(null);
    };

    const selectGroup = (id) => {
        setChannel('group');
        setGroupId(id);
        setPeerId(null);
    };

    const toggleFriend = (id) => {
        setPicked((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const saveGroup = (event) => {
        event.preventDefault();
        const created = createGroup(groupName, picked);

        if (!created) {
            return;
        }

        setCreating(false);
        setGroupName('');
        setPicked([]);
        selectGroup(created.id);
    };

    return (
        <>
            <Head title="Messages" />

            <div className="mx-auto flex h-[calc(100dvh-7.5rem)] max-w-[1100px] overflow-hidden lg:h-screen">
                <aside className="flex w-[92px] min-h-0 shrink-0 flex-col border-r border-line sm:w-[280px]">
                    <div className="flex h-14 items-center border-b border-line px-4">
                        <h1 className="truncate text-base font-semibold">Messages</h1>
                    </div>
                    <div className="ig-scroll flex-1 overflow-y-auto">
                        <div className="flex items-center justify-between px-3 pt-4 pb-1">
                            <p className="hidden text-[11px] font-semibold tracking-wide text-muted uppercase sm:block">
                                Group chats
                            </p>
                            <button
                                type="button"
                                onClick={() => setCreating(true)}
                                className="text-[11px] font-semibold"
                            >
                                New
                            </button>
                        </div>
                        {groups.map((item) => {
                            const unread = unreadInGroup(item.id);

                            return (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => selectGroup(item.id)}
                                className={cn(
                                    'flex w-full items-center gap-3 px-3 py-3 text-left hover:bg-hover',
                                    channel === 'group' && groupId === item.id && 'bg-wash',
                                )}
                            >
                                <span className="relative">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-full border border-line text-xs font-semibold">
                                        {item.name.slice(0, 1)}
                                    </span>
                                    {unread > 0 && (
                                        <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-heart px-1 text-[10px] font-semibold leading-none text-white">
                                            {unread}
                                        </span>
                                    )}
                                </span>
                                <span className="hidden min-w-0 sm:block">
                                    <span className="block truncate text-sm font-semibold">{item.name}</span>
                                    <span className="block truncate text-xs text-muted">
                                        {item.memberIds.length} people
                                    </span>
                                </span>
                            </button>
                            );
                        })}

                        <p className="hidden px-3 pt-4 pb-1 text-[11px] font-semibold tracking-wide text-muted uppercase sm:block">
                            Direct
                        </p>
                        {people.map((user) => {
                            const unread = unreadWith(user.id);

                            return (
                            <button
                                key={user.id}
                                type="button"
                                onClick={() => selectPeer(user.id)}
                                className={cn(
                                    'flex w-full items-center gap-3 px-3 py-3 text-left hover:bg-hover',
                                    channel === 'dm' && peerId === user.id && 'bg-wash',
                                )}
                            >
                                <span className="relative">
                                    <Avatar src={user.avatar} name={user.name} size="md" />
                                    {unread > 0 && (
                                        <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-heart px-1 text-[10px] font-semibold leading-none text-white">
                                            {unread}
                                        </span>
                                    )}
                                </span>
                                <span className="hidden min-w-0 sm:block">
                                    <span className="block truncate text-sm font-semibold">{user.name}</span>
                                    <span className="block truncate text-xs text-muted">{user.title}</span>
                                </span>
                            </button>
                            );
                        })}
                    </div>
                </aside>

                <section className="flex min-h-0 min-w-0 flex-1 flex-col">
                    <header className="flex h-14 items-center gap-3 border-b border-line px-4">
                        {channel === 'group' && group ? (
                            <div>
                                <p className="text-sm font-semibold">{group.name}</p>
                                <p className="text-xs text-muted">
                                    {group.memberIds
                                        .map((id) => (id === viewer.id ? 'You' : userById(id)?.name))
                                        .filter(Boolean)
                                        .join(', ')}
                                </p>
                            </div>
                        ) : peer ? (
                            <>
                                <Avatar src={peer.avatar} name={peer.name} size="sm" />
                                <div>
                                    <p className="text-sm font-semibold">{peer.name}</p>
                                    <p className="text-xs text-muted">{peer.title}</p>
                                </div>
                            </>
                        ) : (
                            <p className="text-sm text-muted">Choose a conversation</p>
                        )}
                    </header>

                    <div className="ig-scroll flex-1 space-y-4 overflow-y-auto px-4 py-5">
                        {!channel && (
                            <p className="py-16 text-center text-sm text-muted">Pick a conversation from the left.</p>
                        )}

                        {channel === 'dm' && !peerId && (
                            <p className="py-16 text-center text-sm text-muted">Pick a teammate from the left.</p>
                        )}

                        {channel === 'dm' &&
                            peerId &&
                            privateMessages.length === 0 && (
                                <p className="py-16 text-center text-sm text-muted">No messages yet. Say hello.</p>
                            )}

                        {channel === 'dm' &&
                            privateMessages.map((message) => {
                                const mine = message.fromId === viewer.id;
                                const author = userById(message.fromId);

                                if (!author) {
                                    return null;
                                }

                                return (
                                    <div
                                        key={message.id}
                                        className={cn('flex gap-2', mine ? 'justify-end' : 'justify-start')}
                                    >
                                        {!mine && <Avatar src={author.avatar} name={author.name} size="xs" />}
                                        <div className={cn('max-w-[75%]', mine && 'text-right')}>
                                            <div
                                                className={cn(
                                                    'inline-block rounded-2xl px-3.5 py-2 text-left text-sm leading-relaxed',
                                                    mine ? 'bg-black text-white' : 'bg-line-soft text-ink',
                                                )}
                                            >
                                                {message.text}
                                                <SharedPost sharedPost={message.sharedPost} />
                                            </div>
                                            <p className="mt-1 text-[11px] text-muted">{timeAgo(message.createdAt)}</p>
                                        </div>
                                    </div>
                                );
                            })}

                        {channel === 'group' &&
                            groupMessages.map((message) => {
                                const mine = message.fromId === viewer.id;
                                const author = userById(message.fromId);

                                if (!author) {
                                    return null;
                                }

                                return (
                                    <div
                                        key={message.id}
                                        className={cn('flex gap-2', mine ? 'justify-end' : 'justify-start')}
                                    >
                                        {!mine && <Avatar src={author.avatar} name={author.name} size="xs" />}
                                        <div className={cn('max-w-[75%]', mine && 'text-right')}>
                                            <p className="mb-1 text-[11px] font-semibold text-muted">
                                                {mine ? 'You' : author.name} · {timeAgo(message.createdAt)}
                                            </p>
                                            <div
                                                className={cn(
                                                    'inline-block rounded-2xl px-3.5 py-2 text-left text-sm leading-relaxed',
                                                    mine ? 'bg-black text-white' : 'bg-line-soft text-ink',
                                                )}
                                            >
                                                {message.text}
                                                <SharedPost sharedPost={message.sharedPost} />
                                            </div>
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
                                placeholder={
                                    channel === 'group' && group
                                        ? `Message ${group.name}…`
                                        : peer
                                          ? `Message ${peer.name}…`
                                          : 'Select a conversation…'
                                }
                                className="max-h-28 min-h-[36px] flex-1 border-0 bg-transparent py-2 text-sm shadow-none focus:outline-none focus:ring-0"
                            />
                            <Button type="submit" size="sm" disabled={!canSend} className="mb-0.5 rounded-full px-3">
                                <SendIcon size={16} />
                                Send
                            </Button>
                        </div>
                    </form>
                </section>
            </div>

            <Dialog open={creating} title="Create group" onClose={() => setCreating(false)}>
                <form onSubmit={saveGroup} className="space-y-4">
                    <label className="block">
                        <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted uppercase">
                            Name
                        </span>
                        <input
                            value={groupName}
                            onChange={(event) => setGroupName(event.target.value)}
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
                    <Button type="submit" block disabled={!groupName.trim() || picked.length === 0}>
                        Create group
                    </Button>
                </form>
            </Dialog>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;
