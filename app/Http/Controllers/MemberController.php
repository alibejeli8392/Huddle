<?php

namespace App\Http\Controllers;

use App\Support\PreviewData;
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
}
