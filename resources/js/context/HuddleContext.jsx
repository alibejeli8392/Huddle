import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';

const HuddleContext = createContext(null);

let memory = null;

function uid(prefix) {
    return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;
}

function normalize(seed) {
    return {
        viewer: seed.viewer,
        members: seed.members ?? [],
        friendships: seed.friendships ?? [],
        messages: (seed.messages ?? []).map((message) => ({
            text: '',
            sharedPost: null,
            ...message,
            unread: Boolean(message.unread),
        })),
        groups: seed.groups ?? [],
        groupMessages: (seed.groupMessages ?? []).map((message) => ({
            text: '',
            sharedPost: null,
            ...message,
            unread: Boolean(message.unread),
        })),
    };
}

export function HuddleProvider({ children }) {
    const { viewer, members, friendships, messages, groups, groupMessages } = usePage().props;

    const [state, setState] = useState(() => {
        if (memory && memory.viewer?.id === viewer?.id && Array.isArray(memory.groups)) {
            return normalize(memory);
        }

        const next = normalize({
            viewer,
            members,
            friendships,
            messages,
            groups,
            groupMessages,
        });
        memory = next;

        return next;
    });

    const commit = useCallback((updater) => {
        setState((prev) => {
            const next = updater(prev);
            memory = next;

            return next;
        });
    }, []);

    const viewerUser = state.viewer;

    const userById = useCallback(
        (id) => state.members.find((member) => member.id === id),
        [state.members],
    );

    const relationship = useCallback(
        (userId) => {
            if (!viewerUser) {
                return 'none';
            }

            if (userId === viewerUser.id) {
                return 'self';
            }

            const match = state.friendships.find(
                (friendship) =>
                    (friendship.fromId === viewerUser.id && friendship.toId === userId) ||
                    (friendship.toId === viewerUser.id && friendship.fromId === userId),
            );

            if (!match) {
                return 'none';
            }

            if (match.status === 'accepted') {
                return 'friends';
            }

            if (match.fromId === viewerUser.id) {
                return 'outgoing';
            }

            return 'incoming';
        },
        [viewerUser, state.friendships],
    );

    const friendsOf = useCallback(
        (userId) => {
            const ids = new Set();

            state.friendships.forEach((friendship) => {
                if (friendship.status !== 'accepted') {
                    return;
                }

                if (friendship.fromId === userId) {
                    ids.add(friendship.toId);
                }

                if (friendship.toId === userId) {
                    ids.add(friendship.fromId);
                }
            });

            return state.members.filter((member) => ids.has(member.id));
        },
        [state.friendships, state.members],
    );

    const incomingRequests = useMemo(() => {
        if (!viewerUser) {
            return [];
        }

        return state.friendships.filter(
            (friendship) => friendship.toId === viewerUser.id && friendship.status === 'pending',
        );
    }, [viewerUser, state.friendships]);

    const unreadMessageCount = useMemo(() => {
        if (!viewerUser) {
            return 0;
        }

        const direct = state.messages.filter(
            (message) => message.unread && message.toId === viewerUser.id,
        ).length;

        const memberGroupIds = new Set(
            state.groups
                .filter((group) => group.memberIds.includes(viewerUser.id))
                .map((group) => group.id),
        );

        const grouped = state.groupMessages.filter(
            (message) =>
                message.unread &&
                message.fromId !== viewerUser.id &&
                memberGroupIds.has(message.groupId),
        ).length;

        return direct + grouped;
    }, [viewerUser, state.messages, state.groupMessages, state.groups]);

    const outgoingRequests = useMemo(() => {
        if (!viewerUser) {
            return [];
        }

        return state.friendships.filter(
            (friendship) => friendship.fromId === viewerUser.id && friendship.status === 'pending',
        );
    }, [viewerUser, state.friendships]);

    const updateProfile = useCallback(
        (patch) => {
            if (!viewerUser) {
                return;
            }

            commit((prev) => ({
                ...prev,
                viewer: { ...prev.viewer, ...patch },
                members: prev.members.map((member) =>
                    member.id === prev.viewer.id ? { ...member, ...patch } : member,
                ),
            }));
        },
        [commit, viewerUser],
    );

    const sendFriendRequest = useCallback(
        (userId) => {
            if (!viewerUser || userId === viewerUser.id) {
                return;
            }

            commit((prev) => {
                const exists = prev.friendships.some(
                    (friendship) =>
                        (friendship.fromId === viewerUser.id && friendship.toId === userId) ||
                        (friendship.toId === viewerUser.id && friendship.fromId === userId),
                );

                if (exists) {
                    return prev;
                }

                return {
                    ...prev,
                    friendships: [
                        ...prev.friendships,
                        { fromId: viewerUser.id, toId: userId, status: 'pending' },
                    ],
                };
            });
        },
        [commit, viewerUser],
    );

    const acceptRequest = useCallback(
        (fromId) => {
            if (!viewerUser) {
                return;
            }

            commit((prev) => ({
                ...prev,
                friendships: prev.friendships.map((friendship) =>
                    friendship.fromId === fromId &&
                    friendship.toId === viewerUser.id &&
                    friendship.status === 'pending'
                        ? { ...friendship, status: 'accepted' }
                        : friendship,
                ),
            }));
        },
        [commit, viewerUser],
    );

    const declineRequest = useCallback(
        (fromId) => {
            if (!viewerUser) {
                return;
            }

            commit((prev) => ({
                ...prev,
                friendships: prev.friendships.filter(
                    (friendship) =>
                        !(
                            friendship.fromId === fromId &&
                            friendship.toId === viewerUser.id &&
                            friendship.status === 'pending'
                        ),
                ),
            }));
        },
        [commit, viewerUser],
    );

    const cancelRequest = useCallback(
        (toId) => {
            if (!viewerUser) {
                return;
            }

            commit((prev) => ({
                ...prev,
                friendships: prev.friendships.filter(
                    (friendship) =>
                        !(
                            friendship.fromId === viewerUser.id &&
                            friendship.toId === toId &&
                            friendship.status === 'pending'
                        ),
                ),
            }));
        },
        [commit, viewerUser],
    );

    const markDirectRead = useCallback(
        (peerId) => {
            if (!viewerUser || !peerId) {
                return;
            }

            commit((prev) => {
                let changed = false;

                const messages = prev.messages.map((message) => {
                    if (message.unread && message.fromId === peerId && message.toId === viewerUser.id) {
                        changed = true;

                        return { ...message, unread: false };
                    }

                    return message;
                });

                if (!changed) {
                    return prev;
                }

                return { ...prev, messages };
            });
        },
        [commit, viewerUser],
    );

    const markGroupRead = useCallback(
        (groupId) => {
            if (!viewerUser || !groupId) {
                return;
            }

            commit((prev) => {
                let changed = false;

                const groupMessages = prev.groupMessages.map((message) => {
                    if (
                        message.unread &&
                        message.groupId === groupId &&
                        message.fromId !== viewerUser.id
                    ) {
                        changed = true;

                        return { ...message, unread: false };
                    }

                    return message;
                });

                if (!changed) {
                    return prev;
                }

                return { ...prev, groupMessages };
            });
        },
        [commit, viewerUser],
    );

    const sendPrivateMessage = useCallback(
        (toId, text) => {
            if (!viewerUser) {
                return null;
            }

            const message = {
                id: uid('m'),
                fromId: viewerUser.id,
                toId,
                text: text.trim(),
                sharedPost: null,
                unread: false,
                createdAt: Date.now(),
            };

            commit((prev) => ({ ...prev, messages: [...prev.messages, message] }));

            return message;
        },
        [commit, viewerUser],
    );

    const createGroup = useCallback(
        (name, memberIds) => {
            const trimmed = name.trim();

            if (!viewerUser || trimmed === '') {
                return null;
            }

            const group = {
                id: uid('g'),
                name: trimmed,
                memberIds: [...new Set([viewerUser.id, ...memberIds])],
            };

            commit((prev) => ({ ...prev, groups: [...prev.groups, group] }));

            return group;
        },
        [commit, viewerUser],
    );

    const sendGroupMessage = useCallback(
        (groupId, text) => {
            const body = text.trim();

            if (!viewerUser || body === '') {
                return null;
            }

            const message = {
                id: uid('gm'),
                groupId,
                fromId: viewerUser.id,
                text: body,
                sharedPost: null,
                unread: false,
                createdAt: Date.now(),
            };

            commit((prev) => ({ ...prev, groupMessages: [...prev.groupMessages, message] }));

            return message;
        },
        [commit, viewerUser],
    );

    const sharePost = useCallback(
        (post, destination) => {
            if (!viewerUser || !post) {
                return;
            }

            const sharedPost = {
                id: post.id,
                authorName: post.author?.name ?? 'Someone',
                text: post.text ?? '',
            };

            commit((prev) => {
                if (destination.kind === 'dm') {
                    return {
                        ...prev,
                        messages: [
                            ...prev.messages,
                            {
                                id: uid('m'),
                                fromId: viewerUser.id,
                                toId: destination.id,
                                text: '',
                                sharedPost,
                                unread: false,
                                createdAt: Date.now(),
                            },
                        ],
                    };
                }

                return {
                    ...prev,
                    groupMessages: [
                        ...prev.groupMessages,
                        {
                            id: uid('gm'),
                            groupId: destination.id,
                            fromId: viewerUser.id,
                            text: '',
                            sharedPost,
                            unread: false,
                            createdAt: Date.now(),
                        },
                    ],
                };
            });
        },
        [commit, viewerUser],
    );

    const threadWith = useCallback(
        (userId) => {
            if (!viewerUser) {
                return [];
            }

            return state.messages
                .filter(
                    (message) =>
                        (message.fromId === viewerUser.id && message.toId === userId) ||
                        (message.fromId === userId && message.toId === viewerUser.id),
                )
                .sort((a, b) => a.createdAt - b.createdAt);
        },
        [viewerUser, state.messages],
    );

    const value = useMemo(
        () => ({
            state,
            viewer: viewerUser,
            userById,
            relationship,
            friendsOf,
            incomingRequests,
            outgoingRequests,
            unreadMessageCount,
            markDirectRead,
            markGroupRead,
            updateProfile,
            sendFriendRequest,
            acceptRequest,
            declineRequest,
            cancelRequest,
            sendPrivateMessage,
            threadWith,
            createGroup,
            sendGroupMessage,
            sharePost,
        }),
        [
            state,
            viewerUser,
            userById,
            relationship,
            friendsOf,
            incomingRequests,
            outgoingRequests,
            unreadMessageCount,
            markDirectRead,
            markGroupRead,
            updateProfile,
            sendFriendRequest,
            acceptRequest,
            declineRequest,
            cancelRequest,
            sendPrivateMessage,
            threadWith,
            createGroup,
            sendGroupMessage,
            sharePost,
        ],
    );

    return <HuddleContext.Provider value={value}>{children}</HuddleContext.Provider>;
}

export function useHuddle() {
    const context = useContext(HuddleContext);

    if (!context) {
        throw new Error('useHuddle must be used within HuddleProvider');
    }

    return context;
}
