<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);



/**
 * Community routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('community', [CommunityController::class, 'store']);
    Route::post('community/invite', [CommunityController::class, 'invite']);
});
