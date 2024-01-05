<?php

namespace App\Http\Controllers;

use App\Models\Files;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;

class FileController extends Controller
{
    public function dashboard(Request $request)
    {
        $files = Files::query()->where('owner_id', '=', Auth::id())
            ->join('users', 'users.id', '=', 'owner_id')
            ->select([
                'users.name AS username',
                'files.*'
            ])
            ->get();

        return Inertia::render('Dashboard', [
            'files' => $files,
        ]);
    }

    public function upload(Request $request)
    {
        $maxFileSize = intval(env('MAX_FILE_SIZE'));

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:'. ($maxFileSize / 1024),
        ]);

        return back();
    }
}
