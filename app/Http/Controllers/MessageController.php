<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * The signed-in member's conversations. `?c=` opens one and marks it read.
     */
    public function index(Request $request): Response
    {
        $conversation = null;

        if ($request->filled('c')) {
            $conversation = Conversation::query()->findOrFail($request->integer('c'));
        }

        return $this->page($request, $conversation);
    }

    /**
     * Open one conversation and mark it read.
     */
    public function show(Request $request, Conversation $conversation): Response
    {
        return $this->page($request, $conversation);
    }

    /**
     * Send a message in a conversation the member belongs to.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('messages.show', $conversation);
    }

    private function page(Request $request, ?Conversation $conversation): Response
    {
        $user = $request->user();

        if ($conversation !== null) {
            Gate::authorize('view', $conversation);
            $conversation->markReadBy($user);
        }

        return Inertia::render('Messages/Index', [
            'conversations' => $user->inboxItems(),
            'activeConversation' => $conversation?->toThread($user),
            'friends' => $user->friendCards(),
        ]);
    }
}
