<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function github()
    {
        $githubUser = Socialite::driver('github')->stateless()->user();

        $user = User::updateOrCreate([
            'email' => $githubUser->email,
        ], [
            'name' => $githubUser->nickname,
            'email' => $githubUser->email,
            'password' => Hash::make(Str::random())
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
