<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlatformController extends Controller
{
    public function index()
    {
        $showAll = false;
        $platforms = Platform::where('is_shown', true)
            ->get();
        return Inertia::render('Platforms/Index', [
            'platforms' => $platforms,
        ]);
    }
}
