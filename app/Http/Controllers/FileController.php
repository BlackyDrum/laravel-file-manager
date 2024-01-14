<?php

namespace App\Http\Controllers;

use App\Models\Files;
use App\Models\SharedFileHasPrivilege;
use App\Models\SharedFiles;
use App\Models\SharedFilesPrivileges;
use App\Models\User;
use App\Rules\ValidateFileName;
use App\Rules\ValidateFileOwner;
use Faker\Core\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
                      'xls', 'xlsx', 'ppt', 'pptx', 'doc', 'docx'];

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

            Log::channel('app')->info('File upload denied: Storage limit exceeded', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'total_file_size' => $uploadFileSize,
                'current_storage_size' => $currentStorageSize,
                'max_storage_size' => $maxStorageSize,
            ]);

            abort(403, "Total file size will exceed your storage limit of $total");
        }


        if (!is_dir(storage_path() . '/app/user_uploads')) {
            mkdir(storage_path() . '/app/user_uploads');
        }

        $path = storage_path() . '/app/user_uploads/' . Auth::id();
        if (!is_dir($path)) {
            mkdir($path);
            Log::channel('app')->info('User upload directory created', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'path' => $path
            ]);
        }

        foreach ($files as $fileWrap) {
            foreach ($fileWrap as $file) {
                $id = $file->store('user_uploads/' . Auth::id());

                Files::query()->create([
                    'identifier' => basename($id),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'owner_id' => Auth::id(),
                    'extension' => $file->extension(),
                ]);

                Log::channel('app')->info('File uploaded successfully', [
                    'ip' => $request->getClientIp(),
                    'id' => Auth::id(),
                    'identifier' => basename($id),
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'owner_id' => Auth::id(),
                    'extension' => $file->extension(),
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
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner('download')]
        ], [
            'files.required' => 'You need to provide at least 1 file',
            'files.array' => 'You need to provide at least 1 file',
            'files.min' => 'You need to provide at least 1 file',
            'files.max' => "You can only download $maxFileDownloadCount files at once",
            'files.*.identifier.required' => 'Invalid file',
            'files.*.identifier.string' => 'Invalid file',
            'files.*.identifier.exists' => 'Invalid file',
        ]);

        $files = $request->input('files');

        $totalSize = 0;
        foreach ($files as $file) {
            $filesize = Files::query()->where('identifier', '=', $file['identifier'])->first()->size;
            $totalSize += $filesize;
        }

        if ($totalSize >  env('MAX_FILE_SIZE')) {
            Log::channel('app')->info('Download aborted: Total size exceeds limit', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'total_size' => $totalSize,
                'max_download_size' => env('MAX_DOWNLOAD_SIZE'),
            ]);

            abort(422, 'Total size exceeds limit');
        }

        $filesPath = storage_path() . '/app/user_uploads/';

        if (!file_exists(storage_path() . '/app/tmp')) {
            mkdir(storage_path() . '/app/tmp');
        }

        $zip = new \ZipArchive();
        $zipPath = '/tmp/' . Auth::id() . '.zip';

        if (!$zip->open(storage_path() . '/app' . $zipPath, \ZipArchive::CREATE)) {
            Log::channel('app')->error('Failed to open zip archive for writing', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'zip_path' => storage_path() . '/app' . $zipPath,
            ]);

            abort(500, 'Server Error');
        }

        foreach ($files as $file) {
            $identifier = $file['identifier'];
            $f = Files::query()->where('identifier', '=', $identifier)->first();

            $zip->addFile($filesPath . $f->owner_id . '/' . $identifier,
                str_ends_with($f->name, $f->extension) ? $f->name : $f->name . '.' . $f->extension);

            Log::channel('app')->info('File added to temporary zip', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'file_identifier' => $identifier,
                'zip_path' => storage_path() . '/app' . $zipPath,
            ]);
        }

        $zip->close();

        register_shutdown_function(function() use ($zipPath, $request) {
            Storage::delete($zipPath);

            Log::channel('app')->info('Zip file deleted on shutdown', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'zip_path' => storage_path() . '/app' . $zipPath,
            ]);
        });

        return Storage::download($zipPath, 'Files.zip');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner('delete')]
        ], [
            'files.*' => 'You need to provide at least 1 file',
            'files.*.identifier.required' => 'Invalid file',
            'files.*.identifier.string' => 'Invalid file',
            'files.*.identifier.exists' => 'Invalid file',
        ]);

        $path = storage_path() . '/app/user_uploads/';

        $deleteCount = 0;

        foreach ($request->input('files') as $file) {
            $identifier = $file['identifier'];
            $f = Files::query()->where('identifier', '=', $identifier)->first();

            unlink($path . $f->owner_id . '/' . $file['identifier']);

            $f->delete();

            Log::channel('app')->info('File deleted', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
                'file_identifier' => $identifier,
                'file_path' => $path . $f->owner_id . '/' . $identifier,
            ]);

            $deleteCount++;
        }

        return \response()->json(['message' => "Deleted $deleteCount files"]);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner('rename')],
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

        Log::channel('app')->info('File renamed', [
            'ip' => $request->getClientIp(),
            'id' => Auth::id(),
            'file_identifier' => $request->input('identifier'),
        ]);

        return \response()->json(['id' => $request->input('identifier'), 'name' => $request->input('filename')]);
    }

    public function share(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*.identifier' => ['bail', 'required', 'string', 'exists:files,identifier', new ValidateFileOwner('share')],
            'email' => 'required|email|exists:users,email',
            'privileges' => 'required|array|min:1',
            'privileges.*.value' => 'required|string|exists:shared_files_privileges,privilege'
        ]);

        $user = User::query()->where('email', '=', $request->input('email'))->first();

        $privileges = $request->input('privileges');

        if ($user->id == Auth::id()) {
            Log::channel('app')->info('User attempted to share files with themselves', [
                'ip' => $request->getClientIp(),
                'id' => Auth::id(),
            ]);

            abort(400, 'You cannot share files with yourself');
        }

        DB::beginTransaction();
        foreach ($request->input('files') as $file) {
            $f = Files::query()->where('identifier', '=', $file['identifier'])->first();

            if ($f->owner_id == $user->id) {
                Log::channel('app')->info('User attempted to share a file with its owner', [
                    'ip' => $request->getClientIp(),
                    'id' => Auth::id(),
                    'file_id' => $f->id,
                ]);

                DB::rollBack();
                abort(400, 'You cannot share a file with it\'s owner');
            }

            $sharedFile = SharedFiles::query()->firstOrCreate([
                'user_id' => $user->id,
                'file_id' => $f->id,
            ]);

            foreach ($privileges as $privilege) {
                $p = $privilege['value'];
                $sf = SharedFileHasPrivilege::query()->firstOrCreate([
                    'shared_file_id' => $sharedFile->id,
                    'privilege_id' => SharedFilesPrivileges::query()->where('privilege', '=', $p)->first()->id,
                ]);

                if ($sf->wasRecentlyCreated) {
                    Log::channel('app')->info('Privilege assigned to shared file', [
                        'ip' => $request->getClientIp(),
                        'id' => Auth::id(),
                        'file_id' => $f->id,
                        'shared_file_id' => $sharedFile->id,
                        'privilege' => $p,
                    ]);
                }
            }
        }
        DB::commit();

        return \response()->json(['message' => 'Files shared']);
    }

    public function preview(Request $request, string $identifier) {
        $file = Files::query()->where('identifier', '=', $identifier)->first();

        $path = '/user_uploads/' . $file->owner_id . '/' . $identifier;

        $mime = Storage::mimeType($path);

        return Storage::download($path,$file->name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
        ]);
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
