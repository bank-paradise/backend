<?php

use App\Http\Controllers\{
    CommunityController,
    BankTransactionController,
    BankAccountController,
    AuthController
};
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

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});


Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);



/**
 * Community routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post("/auth/me", [AuthController::class, 'me']);
    Route::post("/auth/logout", [AuthController::class, 'logout']);

    Route::get('community', [CommunityController::class, 'get']);
    Route::post('community', [CommunityController::class, 'store']);
    Route::post('community/invite', [CommunityController::class, 'invite']);
    Route::post('community/invite/{id}', [CommunityController::class, 'join']);
    Route::post('community/accounts', [CommunityController::class, 'getAccounts']);
});

/**
 * Bank routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('bank', [BankAccountController::class, 'get']);
    Route::post('bank/transaction', [BankTransactionController::class, 'store']);
});
