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
use Illuminate\Support\Facades\Route;

// =====================
// AUTH
// =====================
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// =====================
// ADMIN
// =====================
Route::prefix('admin')->middleware('auth:api')->group(function () {

    // Dashboard
    Route::get('dashboard-data', [DashboardController::class, 'adminData']);

    // Users
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    // Employés
    Route::apiResource('employes', EmployeController::class);

    // Sites
    Route::apiResource('sites', SiteController::class);

    // Affectations
    Route::apiResource('affectation_listes', AffectationListeController::class)
        ->except(['show', 'edit', 'update']);

    // ✅ Bien déclarer la route d'ajout d'employé
    Route::post('affectation_listes/{id}/employes', [AffectationListeController::class, 'ajouterEmployeAffectation'])
        ->name('affectation_listes.ajouterEmploye');

    // Opérations sur les pivots
    Route::prefix('affectation_liste_employe')->group(function () {
        Route::put('{pivotId}', [AffectationListeController::class, 'updatePivot']);
        Route::delete('{pivotId}', [AffectationListeController::class, 'supprimerEmploye']);
    });

    // Congés
    Route::post('conges', [CongeController::class, 'store']);
    Route::get('conges', [CongeController::class, 'index']);
    Route::put('conges/{id}', [CongeController::class, 'update']);
    Route::delete('conges/{id}', [CongeController::class, 'destroy']);
    Route::get('conges/download/{id}', [CongeController::class, 'download']);
});

// =====================
// SECRETAIRE
// =====================
Route::prefix('secretaire')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceJournaliereController::class, 'index']);
    Route::post('presences', [PresenceJournaliereController::class, 'store']);
});

// =====================
// MANAGER
// =====================
Route::prefix('manager')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceValidationController::class, 'index']);
    Route::post('presences/validate', [PresenceValidationController::class, 'validateAll']);
    Route::post('presences/validate/{id}', [PresenceValidationController::class, 'validateAffectation']);

});
