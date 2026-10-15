<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PreviewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Everyone at the company except the signed-in member.
     * Messages on this page are still preview data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Members/Index', [
            ...PreviewData::forUser($user),
            'directory' => $user->directoryCards(),
        ]);
    }

    /**
     * Another member's profile. Your own profile lives at /me.
     */
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer->is($user)) {
            return redirect()->route('profile.show');
        }

        return Inertia::render('Members/Show', [
            ...PreviewData::forUser($viewer),
            'member' => $viewer->presentMember($user),
            'posts' => $user->feedItemsFor($viewer),
        ]);
    }
}
