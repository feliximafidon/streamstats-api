<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

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
    return view('welcome');
});

Route::get('/auth/login/twitch', function() {
    return Socialite::driver('twitch')->scopes(['user:read:email', 'user:read:follows', 'channel:read:subscriptions'])->redirect();
});

Route::get('/auth/callback/{driver}', function() {
    $user = Socialite::driver('twitch')->user();

    dd($user);
})->where('driver', ['twitch']);

Route::get('/{any}', function() {
    return redirect('/');
})->where('any', '.*');