<?php

namespace App\Http\Controllers;

use App\ConversationType;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ConversationController extends Controller
{
    /**
     * Open a direct chat with a friend, or create a group of friends.
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $user = $request->user();

        $conversation = $request->string('type')->toString() === ConversationType::Direct->value
            ? Conversation::findOrCreateDirect($user, User::query()->findOrFail($request->integer('friend_id')))
            : Conversation::startGroup(
                $user,
                $request->string('name')->toString(),
                $request->collect('member_ids')->map(fn (mixed $id): int => (int) $id)->all(),
            );

        return redirect()->route('messages.show', $conversation);
    }
}
