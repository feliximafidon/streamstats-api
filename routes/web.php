<?php

use App\Http\Controllers\UserController;
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

Route::get('/auth/login/{twitch}', [UserController::class, 'loginInitiate'])->whereIn('driver', ['twitch']);

Route::get('/auth/callback/{driver}', [UserController::class, 'loginCallback'])->whereIn('driver', ['twitch']);

Route::get('/{any}', [UserController::class, 'lost'])->where('any', '.*');