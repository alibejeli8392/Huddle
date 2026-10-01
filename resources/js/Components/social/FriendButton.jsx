import Button from '@/Components/ui/Button';
import { useHuddle } from '@/context/HuddleContext';

export default function FriendButton({ userId, size = 'sm', block = false }) {
    const { relationship, sendFriendRequest, acceptRequest, declineRequest, cancelRequest } =
        useHuddle();
    const rel = relationship(userId);

    if (rel === 'self') {
        return null;
    }

    if (rel === 'none') {
        return (
            <Button size={size} block={block} onClick={() => sendFriendRequest(userId)}>
                Add Friend
            </Button>
        );
    }

    if (rel === 'outgoing') {
        return (
            <Button size={size} block={block} variant="secondary" onClick={() => cancelRequest(userId)}>
                Request Sent
            </Button>
        );
    }

    if (rel === 'friends') {
        return (
            <Button size={size} block={block} variant="secondary" disabled>
                Friends
            </Button>
        );
    }

    return (
        <div className={block ? 'flex w-full gap-2' : 'flex gap-2'}>
            <Button size={size} block={block} onClick={() => acceptRequest(userId)}>
                Accept
            </Button>
            <Button size={size} block={block} variant="secondary" onClick={() => declineRequest(userId)}>
                Decline
            </Button>
        </div>
    );
}
