<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Only a member can open a conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isMember($user, $conversation);
    }

    /**
     * Only a member can send a message.
     */
    public function send(User $user, Conversation $conversation): bool
    {
        return $this->isMember($user, $conversation);
    }

    private function isMember(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->whereKey($user->id)->exists();
    }
}
