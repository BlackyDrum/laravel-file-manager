<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/auth/github/redirect', function () {
    return \Laravel\Socialite\Facades\Socialite::driver('github')->redirect();
});
Route::get('/auth/github/callback', [\App\Http\Controllers\SocialiteController::class, 'github']);

Route::get('/auth/google/redirect', function () {
    return \Laravel\Socialite\Facades\Socialite::driver('google')->redirect();
});
Route::get('/auth/google/callback', [\App\Http\Controllers\SocialiteController::class, 'google']);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/upload', [\App\Http\Controllers\FileController::class, 'upload'])->name('upload');
    Route::post('download', [\App\Http\Controllers\FileController::class, 'download'])->name('download');
    Route::post('/share', [\App\Http\Controllers\FileController::class, 'share'])->name('share');
    Route::delete('/files', [\App\Http\Controllers\FileController::class, 'delete'])->name('delete');
    Route::patch('/files/rename', [\App\Http\Controllers\FileController::class, 'rename'])->name('rename');

    Route::get('/file/{identifier}/preview', [\App\Http\Controllers\FileController::class, 'preview'])->name('preview');

    Route::get('/dashboard', [\App\Http\Controllers\FileController::class, 'dashboard'])->name('dashboard');
});

require __DIR__.'/auth.php';
