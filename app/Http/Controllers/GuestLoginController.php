<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'guest@huddle.app'],
            [
                'name' => 'Guest',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
