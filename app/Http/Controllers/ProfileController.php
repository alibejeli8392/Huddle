<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StoreAvatarRequest;
use App\Http\Requests\UpdateProfileDetailsRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the profile page. The design profile and account settings are tabs on this screen.
     */
    public function show(Request $request): Response
    {
        $tab = $request->query('tab') === 'account' ? 'account' : 'profile';

        return Inertia::render('Profile/Show', [
            'tab' => $tab,
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'profile' => $this->profilePayload($request->user()),
        ]);
    }

    /**
     * Update title, department, and bio. Name and email stay on account settings.
     */
    public function updateDetails(UpdateProfileDetailsRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only(['title', 'department', 'bio']));

        return Redirect::route('profile.show')->with('status', 'profile-updated');
    }

    /**
     * Store a new profile photo and drop the previous local file.
     */
    public function storeAvatar(StoreAvatarRequest $request): RedirectResponse
    {
        $path = $request->file('avatar')->store('avatars', 'public');

        $request->user()->storeAvatar($path);

        return Redirect::route('profile.show');
    }

    /**
     * Remove the profile photo.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->clearAvatar();

        return Redirect::route('profile.show');
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'title' => $user->title ?? '',
            'department' => $user->department ?? '',
            'bio' => $user->bio ?? '',
            'avatar' => $user->avatar_url,
            'postCount' => $user->posts()->count(),
            'friendCount' => $user->friends()->count(),
        ];
    }

    /**
     * Send the old account URL to the account tab on the profile page.
     */
    public function edit(): RedirectResponse
    {
        return Redirect::route('profile.show', ['tab' => 'account']);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.show', ['tab' => 'account']);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
