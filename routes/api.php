<?php

use App\Http\Controllers\{
    CommunityController,
    BankTransactionController,
    BankAccountController,
    AuthController,
    BankSalaryRequestController,
    CompanyEmployeesController,
    CharactersController,
    CommunityInvitationLinkController
};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $apiInformations = \App\Models\ApiInformations::first();
    return response()->json([
        'status' => 'ok', 'version' => $apiInformations->version,
        "environment" => app()->environment(),
    ]);
});


Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);


/**
 * Community routes
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post("/auth/me", [AuthController::class, 'me']);
    Route::put("/auth/me", [AuthController::class, 'edit']);
    Route::delete("/auth/me", [AuthController::class, 'delete']);
    Route::post("/auth/logout", [AuthController::class, 'logout']);

    Route::get('community', [CommunityController::class, 'get']);
    Route::put('community', [CommunityController::class, 'update']);
    Route::post('community', [CommunityController::class, 'store']);
    Route::delete('community', [CommunityController::class, 'deleteCommunity']);
    Route::post('community/invite/{id}', [CommunityController::class, 'join']);
    Route::get('community/link/invite/{code}', [CommunityInvitationLinkController::class, 'getInvitationLinkInformations']);
    Route::put('community/link/invite/{code}', [CommunityInvitationLinkController::class, 'useInvitationLink']);
    Route::post('community/accounts', [CommunityController::class, 'getAccounts']);
    Route::get('community/accounts/all', [BankAccountController::class, 'getAllAccounts']);

    /**
     * Staff route
     */
    Route::get('community/invitations', [CommunityController::class, 'getInvitations']);
    Route::get('community/invitations/link', [CommunityInvitationLinkController::class, 'getInvitationLink']);
    Route::delete('community/invitations/link/reset', [CommunityInvitationLinkController::class, 'resetInvitationsLink']);

    Route::get('community/transactions', [CommunityController::class, 'getTransactions']);
    Route::get('community/members', [CommunityController::class, 'getMembers']);
    Route::post('community/invite', [CommunityController::class, 'invite']);

    Route::post('community/transactions/inject', [BankTransactionController::class, 'injectMoney']);

    Route::put('community/role', [CommunityController::class, 'changeRole']);
    Route::post('community/kick', [CommunityController::class, 'kickMember']);

    Route::get('community/salary', [BankSalaryRequestController::class, 'getAll']);
    Route::put('community/salary', [BankSalaryRequestController::class, 'answer']);
});

/**
 * Bank routes
 */
Route::middleware('auth:sanctum', 'rp.community')->group(function () {
    Route::get('bank', [BankAccountController::class, 'get']);
    Route::post('bank/transaction', [BankTransactionController::class, 'store']);

    // Salary routes
    Route::post('bank/salary', [BankSalaryRequestController::class, 'create']);
    Route::get('bank/salary/last', [BankSalaryRequestController::class, 'getLast']);

    // Company routes
    Route::post('bank/company', [BankAccountController::class, 'createCompanyAccount']);
    Route::delete('bank/company/{id}', [BankAccountController::class, 'removeCompany']);
    Route::post('bank/company/salary', [BankTransactionController::class, 'sendSalary']);
    Route::put('bank/company/salary', [BankTransactionController::class, 'changeSalary']);
    Route::post('bank/company/employee', [CompanyEmployeesController::class, 'addEmployee']);
    Route::post('bank/company/employee/fire', [CompanyEmployeesController::class, 'fireEmployee']);
});


/**
 * Character routes
 */
Route::middleware('auth:sanctum', 'rp.community')->group(function () {
    Route::get('character', [CharactersController::class, 'getCharacter']);
    Route::post('character', [CharactersController::class, 'createCharacter']);
});
