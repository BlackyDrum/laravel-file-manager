<?php

namespace App\Http\Controllers;

use App\Models\Files;
use App\Rules\ValidateFileName;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
            'files.*' => ['bail', 'required', 'file', 'max:' . ($maxFileSize / 1024), new ValidateFileName()],
        ]);

        $files = $request->allFiles();

        $path = storage_path() . '/app/user_uploads/' . Auth::id();
        if (!is_dir($path)) {
            mkdir($path);
        }

        foreach ($files as $file) {
            $id = $file[0]->store('user_uploads/' . Auth::id());

            Files::query()->create([
                'identifier' => substr($id, strrpos($id, '/') + 1),
                'name' => $file[0]->getClientOriginalName(),
                'size' => $file[0]->getSize(),
                'owner_id' => Auth::id(),
            ]);
        }

        return back();
    }
}
