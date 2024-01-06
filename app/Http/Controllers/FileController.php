<?php

namespace App\Http\Controllers;

use App\Models\Files;
use App\Rules\ValidateFileName;
use App\Rules\ValidateFileOwner;
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
                'files.identifier',
                'files.name',
                'files.size',
                'files.owner_id',
                'files.updated_at',
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

        foreach ($files as $fileWrap) {
            foreach ($fileWrap as $file) {
                $id = $file->store('user_uploads/' . Auth::id());

                Files::query()->create([
                    'identifier' => basename($id),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'owner_id' => Auth::id(),
                ]);
            }
        }

        return back();
    }

    public function delete(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner()]
        ], [
            'files.*' => 'You need to provide at least 1 file',
            'files.*.identifier.*' => 'Invalid file'
        ]);

        $path = storage_path() . '\app\user_uploads\\' . Auth::id() . '\\';

        $deleteCount = 0;

        foreach ($request->input('files') as $file) {
            Files::query()->where('identifier', '=', $file['identifier'])->delete();
            unlink($path . $file['identifier']);

            $deleteCount++;
        }

        return \response()->json(['message' => "Deleted $deleteCount files"]);
    }
}
