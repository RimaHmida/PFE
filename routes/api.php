<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EmployeController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\AffectationListeController;
use App\Http\Controllers\Admin\CongeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Secretaire\PresenceJournaliereController;
use App\Http\Controllers\Manager\PresenceValidationController;
use App\Http\Controllers\Paie\ValidationPaieController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
// AUTH
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
Route::post('change-password', [AuthController::class, 'changePassword']);
Route::get('/profile', [ProfileController::class, 'me']);
Route::post('/profile/update', [ProfileController::class, 'update']);
});

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);


// ADMIN
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::get('dashboard-data', [DashboardController::class, 'adminData']);
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']); // création des comptes
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    Route::apiResource('employes', EmployeController::class);
    Route::apiResource('sites', SiteController::class);

    Route::apiResource('affectation_listes', AffectationListeController::class)->except(['show', 'edit', 'update']);
    Route::post('affectation_listes/{id}/employes', [AffectationListeController::class, 'ajouterEmployeAffectation']);
    
    Route::prefix('affectation_liste_employe')->group(function () {
        Route::put('{pivotId}', [AffectationListeController::class, 'updatePivot']);
        Route::delete('{pivotId}', [AffectationListeController::class, 'supprimerEmploye']);
    });

    Route::post('conges', [CongeController::class, 'store']);
    Route::get('conges', [CongeController::class, 'index']);
    Route::put('conges/{id}', [CongeController::class, 'update']);
    Route::delete('conges/{id}', [CongeController::class, 'destroy']);
    Route::get('conges/download/{id}', [CongeController::class, 'download']);
});

// SECRETAIRE
Route::prefix('secretaire')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceJournaliereController::class, 'index']);
    Route::post('presences', [PresenceJournaliereController::class, 'store']);
});

// MANAGER
Route::prefix('manager')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceValidationController::class, 'index']);
    Route::post('presences/validate', [PresenceValidationController::class, 'validateAll']);
    Route::post('presences/validate/{id}', [PresenceValidationController::class, 'validateAffectation']);
});

// PAIE
Route::prefix('paie')->middleware('auth:api')->group(function () {
    Route::get('validations', [ValidationPaieController::class, 'index']);
    Route::get('validations/{id}/download', [ValidationPaieController::class, 'download']);
});
