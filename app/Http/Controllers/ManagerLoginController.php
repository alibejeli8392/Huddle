<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerLoginController extends Controller
{
    /**
     * Demo-only: remove this controller and route before real use.
     * Signs in as manager@huddle.app (find-or-create) and opens Tasks.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->first();
        $department = is_string($guest?->department) && $guest->department !== ''
            ? $guest->department
            : 'Design';

        $user = User::query()->firstOrCreate(
            ['email' => 'manager@huddle.app'],
            [
                'name' => 'Leslie Chen',
                'password' => 'password',
                'email_verified_at' => now(),
                'title' => 'Design Manager',
                'department' => $department,
                'bio' => 'I keep the day-to-day work clear so the team can ship.',
                'avatar_path' => 'https://images.pexels.com/photos/1181686/pexels-photo-1181686.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=640&w=640',
                'role' => UserRole::Manager,
            ],
        );

        if ($user->role !== UserRole::Manager) {
            $user->forceFill([
                'role' => UserRole::Manager,
                'department' => $department,
            ])->save();
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('tasks.index');
    }
}
