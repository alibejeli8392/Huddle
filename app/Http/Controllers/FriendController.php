<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFriendRequest;
use App\Models\User;
use App\Support\PreviewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FriendController extends Controller
{
    /**
     * Friends, incoming requests, and requests you sent.
     * Messages on this page are still preview data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Friends/Index', [
            ...PreviewData::forUser($user),
            ...$user->friendLists(),
        ]);
    }

    /**
     * Send a friend request, or accept one this person already sent.
     */
    public function store(StoreFriendRequest $request, User $user): RedirectResponse
    {
        $request->user()->requestFriendshipWith($user);

        return back();
    }
}
