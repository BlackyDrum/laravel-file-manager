<?php

namespace App\Http\Middleware;

use App\Models\Files;
use App\Models\SharedFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared_files = SharedFiles::query()
            ->where('user_id', Auth::id())
            ->join('files', 'files.id', '=', 'shared_files.file_id')
            ->join('users', 'users.id', '=', 'files.owner_id')
            ->select([
                'users.name AS username',
                'files.identifier',
                'files.name',
                'files.size',
                'files.owner_id',
                'files.updated_at',
            ]);

        $files = Files::query()
            ->where('owner_id', Auth::id())
            ->join('users', 'users.id', '=', 'owner_id')
            ->select([
                'users.name AS username',
                'files.identifier',
                'files.name',
                'files.size',
                'files.owner_id',
                'files.updated_at',
            ])
            ->union($shared_files)
            ->get();


        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'files' => $files
        ];
    }
}
