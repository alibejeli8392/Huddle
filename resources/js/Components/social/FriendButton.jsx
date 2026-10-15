import Button from '@/Components/ui/Button';
import { router } from '@inertiajs/react';

export default function FriendButton({ user, size = 'sm', block = false }) {
    const post = (url) => router.post(url, {}, { preserveScroll: true });
    const remove = (url) => router.delete(url, { preserveScroll: true });

    if (user.relationship === 'none') {
        return (
            <Button size={size} block={block} onClick={() => post(route('friends.store', user.id))}>
                Add Friend
            </Button>
        );
    }

    if (user.relationship === 'request_sent') {
        return (
            <Button
                size={size}
                block={block}
                variant="secondary"
                onClick={() => remove(route('friendships.destroy', user.friendshipId))}
            >
                Request Sent
            </Button>
        );
    }

    if (user.relationship === 'friends') {
        return (
            <Button
                size={size}
                block={block}
                variant="secondary"
                onClick={() => remove(route('friendships.destroy', user.friendshipId))}
            >
                Unfriend
            </Button>
        );
    }

    return (
        <div className={block ? 'flex w-full gap-2' : 'flex gap-2'}>
            <Button size={size} block={block} onClick={() => post(route('friendships.accept', user.friendshipId))}>
                Accept
            </Button>
            <Button
                size={size}
                block={block}
                variant="secondary"
                onClick={() => post(route('friendships.decline', user.friendshipId))}
            >
                Decline
            </Button>
        </div>
    );
}
