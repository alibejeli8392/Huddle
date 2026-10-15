<?php

namespace App\Http\Controllers;

use App\FriendshipStatus;
use App\Models\Friendship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FriendshipController extends Controller
{
    public function accept(Friendship $friendship): RedirectResponse
    {
        Gate::authorize('respond', $friendship);

        $friendship->update(['status' => FriendshipStatus::Accepted]);

        return back();
    }

    public function decline(Friendship $friendship): RedirectResponse
    {
        Gate::authorize('respond', $friendship);

        $friendship->update(['status' => FriendshipStatus::Declined]);

        return back();
    }

    public function destroy(Friendship $friendship): RedirectResponse
    {
        Gate::authorize('delete', $friendship);

        $friendship->delete();

        return back();
    }
}
