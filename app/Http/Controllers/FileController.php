<?php

namespace App\Http\Controllers;

use App\Models\Files;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FileController extends Controller
{
    public function dashboard(Request $request)
    {
        $files = Files::query()->where('owner_id', '=', Auth::id())->get();

        return Inertia::render('Dashboard', [
            'files' => $files,
        ]);
    }
}
