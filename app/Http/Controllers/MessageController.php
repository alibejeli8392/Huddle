<?php

namespace App\Http\Controllers;

use App\Support\PreviewData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Messages/Index', PreviewData::forUser($request->user()));
    }
}
