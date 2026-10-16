<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    /**
     * Home feed of posts, likes, and comments.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $posts = Post::query()
            ->with([
                'user',
                'comments' => fn ($query) => $query->oldest(),
                'comments.user',
            ])
            ->withCount(['likers', 'comments'])
            ->withExists([
                'likers as liked' => fn ($query) => $query->whereKey($user->id),
            ])
            ->latest()
            ->get()
            ->map(fn (Post $post): array => $post->toFeedItem($user))
            ->all();

        return Inertia::render('Feed/Index', [
            'posts' => $posts,
            'shareTargets' => $user->shareTargets(),
        ]);
    }
}
