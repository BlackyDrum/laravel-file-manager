<?php

namespace App\Http\Controllers;

use App\Models\Files;
use App\Models\SharedFiles;
use App\Models\User;
use App\Rules\ValidateFileName;
use App\Rules\ValidateFileOwner;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use const http\Client\Curl\AUTH_ANY;

class FileController extends Controller
{
    public function dashboard(Request $request)
    {
        return Inertia::render('Dashboard');
    }

    public function upload(Request $request)
    {
        $maxFileSize = intval(env('MAX_FILE_SIZE'));
        $maxFileUploadCount = intval(env('MAX_FILE_UPLOAD_COUNT'));
        $maxStorageSize = intval(env('MAX_STORAGE_SIZE'));

        $fileTypes = ['zip', 'tar', 'rar', 'gzip', '7z',
                      'mp3', 'mp4', 'mpeg', 'wav', 'ogg', 'opus',
                      'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg',
                      'css', 'html', 'php', 'c', 'cpp', 'h', 'hpp', 'js', 'java', 'py',
                      'txt', 'pdf', 'log',
                      'webm', 'mpeg4', '3gpp', 'mov', 'avi', 'wmv', 'flv', 'ogg',
                      'xls', 'xlsx', 'ppt', 'pptx', 'doc', 'docx', 'xps'];

        $request->validate([
            'files' => 'required|array|min:1|max:' . $maxFileUploadCount,
            'files.*' => ['bail', 'required', 'file', \Illuminate\Validation\Rules\File::types($fileTypes) ,'max:' . ($maxFileSize / 1024), new ValidateFileName()],
        ], [
            'files.required' => 'You need to provide at least 1 file',
            'files.array' => 'You need to provide at least 1 file',
            'files.min' => 'You need to provide at least 1 file',
            'files.max' => "You can only upload $maxFileUploadCount files at once",
            'files.*.mimes' => 'The file must be a file of type: :values',
            'files.*.required' => 'Invalid file',
            'files.*.file' => 'Invalid file',
        ]);

        $files = $request->allFiles();

        $currentUserFiles = Files::query()->where('owner_id', '=', Auth::id())->select(['size'])->get();
        $currentStorageSize = 0;
        foreach ($currentUserFiles as $file) {
            $currentStorageSize += $file->size;
        }

        $uploadFileSize = 0;
        foreach ($files as $fileWrap) {
            foreach ($fileWrap as $file) {
                $uploadFileSize += $file->getSize();
            }
        }

        if ($currentStorageSize + $uploadFileSize > $maxStorageSize) {
            $total = $this->formatBytes($maxStorageSize, 0);
            abort(403, "Total file size will exceed your storage limit of $total");
        }


        if (!is_dir(storage_path() . '/app/user_uploads')) {
            mkdir(storage_path() . '/app/user_uploads');
        }

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
        $maxFileDownloadCount = env('MAX_FILE_DOWNLOAD_COUNT');

        $request->validate([
            'files' => 'required|array|min:1|max:' . $maxFileDownloadCount,
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner()]
        ], [
            'files.required' => 'You need to provide at least 1 file',
            'files.array' => 'You need to provide at least 1 file',
            'files.min' => 'You need to provide at least 1 file',
            'files.max' => "You can only download $maxFileDownloadCount files at once",
            'files.*.identifier.*' => 'Invalid file'
        ]);

        $files = $request->input('files');

        $totalSize = 0;
        foreach ($files as $file) {
            $filesize = Files::query()->where('identifier', '=', $file['identifier'])->first()->size;
            $totalSize += $filesize;
        }

        if ($totalSize >  env('MAX_FILE_SIZE')) {
            abort(422, 'Total size exceeds limit');
        }

        $filesPath = storage_path() . '/app/user_uploads/';

        if (!file_exists(storage_path() . '/app/tmp')) {
            mkdir(storage_path() . '/app/tmp');
        }

        $zip = new \ZipArchive();
        $zipPath = '/tmp/' . Auth::id() . '.zip';

        if (!$zip->open(storage_path() . '/app' . $zipPath, \ZipArchive::CREATE)) {
            abort(500, 'Server Error');
        }

        foreach ($files as $file) {
            $identifier = $file['identifier'];
            $f = Files::query()->where('identifier', '=', $identifier)->first();

            $zip->addFile($filesPath . $f->owner_id . '/' . $identifier, $f->name);
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

    public function rename(Request $request)
    {
        $request->validate([
            'identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner()],
            'filename' => ['bail', 'required', 'string', 'max:' . env('MAX_FILE_NAME_SIZE'),
                Rule::unique('files', 'name')->where(function ($query) use ($request) {
                    return $query->where('owner_id', Auth::id());
                }),
            ]
        ], [
            'identifier.required' => 'Invalid file',
            'identifier.string' => 'Invalid file',
            'identifier.exists' => 'Invalid file',
            'filename.unique' => 'You already have a file with the name ' . $request->input('filename')
        ]);

        Files::query()->where('identifier', '=', $request->input('identifier'))
            ->update([
                'name' => $request->input('filename')
            ]);

        return \response()->json(['id' => $request->input('identifier'), 'name' => $request->input('filename')]);
    }

    public function share(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner()],
            'email' => 'required|string|exists:users,email'
        ]);

        $user = User::query()->where('email', '=', $request->input('email'))->first();

        if ($user->id == Auth::id()) {
            abort(400, 'You cannot share files with yourself');
        }

        foreach ($request->input('files') as $file) {
            $f = Files::query()->where('identifier', '=', $file['identifier'])->first();

            SharedFiles::query()->firstOrCreate([
                'user_id' => $user->id,
                'file_id' => $f->id,
            ]);
        }

        return \response()->json(['message' => 'Files shared']);
    }

    private function formatBytes($bytes, $decimals = 2) {
        if (!is_numeric($bytes) || $bytes < 0) {
            return '0.00 B';
        }

        $k = 1024;
        $dm = $decimals < 0 ? 0 : $decimals;
        $sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

        $i = floor(log($bytes) / log($k));

        return sprintf('%s %s', number_format($bytes / pow($k, $i), $dm), $sizes[$i]);
    }
}
