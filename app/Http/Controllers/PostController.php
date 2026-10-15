<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Store a new post on the home feed.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $request->user()->posts()->create([
            'body' => $request->validated('body'),
        ]);

        return back();
    }

    /**
     * Add or remove the current member's like.
     */
    public function toggleLike(Request $request, Post $post): RedirectResponse
    {
        $user = $request->user();

        if ($post->likers()->whereKey($user->id)->exists()) {
            $post->likers()->detach($user);
        } else {
            $post->likers()->attach($user);
        }

        return back();
    }

    /**
     * Delete a post the current member wrote.
     */
    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return back();
    }
}
