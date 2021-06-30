<?php

use App\Http\Controllers\APIUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Clue\StreamFilter\fun;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [APIUserController::class, 'fetch']);
    Route::post('user', [APIUserController::class, 'updateProfile']);
    Route::post('user/photo', [APIUserController::class, 'uploadPhoto']);
    Route::get('logout', [APIUserController::class, 'logout']);
});

Route::post('login', [APIUserController::class, 'login']);
Route::post('register', [APIUserController::class, 'register']);
