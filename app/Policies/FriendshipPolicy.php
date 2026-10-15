<?php

namespace App\Policies;

use App\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    /**
     * The person who received a pending request can accept or decline it.
     */
    public function respond(User $user, Friendship $friendship): bool
    {
        return (int) $friendship->addressee_id === (int) $user->id
            && $friendship->status === FriendshipStatus::Pending;
    }

    /**
     * Cancel a request you sent, or end an accepted friendship from either side.
     */
    public function delete(User $user, Friendship $friendship): bool
    {
        if ($friendship->status === FriendshipStatus::Pending) {
            return (int) $friendship->requester_id === (int) $user->id;
        }

        if ($friendship->status === FriendshipStatus::Accepted) {
            return in_array((int) $user->id, [(int) $friendship->requester_id, (int) $friendship->addressee_id], true);
        }

        return false;
    }
}
