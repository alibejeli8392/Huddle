<?php

namespace App\Http\Controllers;

use App\Support\PreviewData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Feed/Index', PreviewData::forUser($request->user()));
    }
}
