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
use const http\Client\Curl\AUTH_ANY;

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

        $fileTypes = ['zip', 'tar', 'rar', 'gzip', '7z',
                      'mp3', 'mp4', 'mpeg', 'wav', 'ogg', 'opus',
                      'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg',
                      'css', 'html', 'php', 'c', 'cpp', 'h', 'hpp', 'js', 'java', 'py',
                      'txt', 'pdf', 'log',
                      'webm', 'mpeg4', '3gpp', 'mov', 'avi', 'wmv', 'flv', 'ogg',
                      'xls', 'xlsx', 'ppt', 'pptx', 'doc', 'docx', 'xps'];

        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => ['bail', 'required', 'file', \Illuminate\Validation\Rules\File::types($fileTypes) ,'max:' . ($maxFileSize / 1024), new ValidateFileName()],
        ], [
            'files.required' => 'You need to provide at least 1 file',
            'files.array' => 'You need to provide at least 1 file',
            'files.min' => 'You need to provide at least 1 file',
            'files.max' => 'You can only upload 10 files at once',
            'files.*.mimes' => 'The file must be a file of type: :values',
            'files.*.required' => 'Invalid file',
            'files.*.file' => 'Invalid file',
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

    public function download(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner()]
        ], [
            'files.required' => 'You need to provide at least 1 file',
            'files.array' => 'You need to provide at least 1 file',
            'files.min' => 'You need to provide at least 1 file',
            'files.max' => 'You can only download 10 files at once',
            'files.*.identifier.*' => 'Invalid file'
        ]);

        $files = $request->input('files');

        $filesPath = storage_path() . '/app/user_uploads/' . Auth::id() . '/';

        $zip = new \ZipArchive();
        $zipPath = '/tmp/' . Auth::id() . '.zip';

        if (!$zip->open(storage_path() . '/app' . $zipPath, \ZipArchive::CREATE)) {
            abort(500, 'Server Error');
        }

        foreach ($files as $file) {
            $identifier = $file['identifier'];
            $filename = Files::query()->where('identifier', '=', $identifier)->first()->name;

            $zip->addFile($filesPath . $identifier, $filename);
        }

        $zip->close();

        register_shutdown_function(function() use ($zipPath) {
            Storage::delete($zipPath);
        });

        return Storage::download($zipPath, 'Files.zip');
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
