<?php
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EmployeController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\AffectationListeController;
use App\Http\Controllers\Secretaire\PresenceJournaliereController;
use App\Http\Controllers\Manager\PresenceValidationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CongeController;
use App\Http\Controllers\Admin\AffectationListeEmployeController;

// Routes pour l'administration
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::apiResource('employes', EmployeController::class);
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('affectation_listes', AffectationListeController::class)
         ->except(['show', 'edit', 'update']);
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    //delete employe de laffectation
    Route::delete('/affectation_liste_employe/{pivotId}', [AffectationListeEmployeController::class, 'destroy']);
    Route::put('/affectation_liste_employe/{pivotId}', [AffectationListeEmployeController::class, 'update']);
    Route::post('/affectation_liste_employe/ajouter-employe', [AffectationListeEmployeController::class, 'ajouterEmploye']);

});

// Routes pour la secrétaire
Route::prefix('secretaire')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceJournaliereController::class, 'index']);
    Route::post('presences', [PresenceJournaliereController::class, 'store']);
});

// Routes pour le manager (protégées par auth:api)
Route::prefix('manager')->middleware('auth:api')->group(function () {
    Route::get('presences', [PresenceValidationController::class, 'index']);
    Route::post('presences/validate', [PresenceValidationController::class, 'validateAll']);
});


// Route pour le login (stateless)
Route::post('login', [AuthController::class, 'login']);

// Routes protégées – l'accès nécessite un token JWT valide
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route pour le register
Route::post('register', [AuthController::class, 'register']);

//dashboard admin
Route::get('/admin/dashboard-data', [DashboardController::class, 'adminData']);

//conge
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::post('/conges', [CongeController::class, 'store']);
    Route::get('/conges', [CongeController::class, 'index']); 
    //Route::post('/conges/{id}', [CongeController::class, 'update']); 
    Route::put('/conges/{id}', [CongeController::class, 'update']);
    Route::delete('/conges/{id}', [CongeController::class, 'destroy']);
    Route::get('affectations/{id}/recuperation', [AffectationListeController::class, 'recuperationParAffectation']);
 

});
Route::get('/admin/conges/download/{id}', [CongeController::class, 'download']);

