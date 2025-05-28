<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EmployeController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\AffectationListeController;
use App\Http\Controllers\Secretaire\PresenceJournaliereController;
use App\Http\Controllers\Manager\PresenceValidationController;

Route::get('/', function () {
    return view('welcome');
});

//admin
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth', 'verified'])->name('admin.dashboard');
//employe
Route::resource('admin/employes', \App\Http\Controllers\Admin\EmployeController::class)
    ->names('admin.employes')
    ->middleware(['auth', 'verified']);

//site
Route::resource('admin/sites', \App\Http\Controllers\Admin\SiteController::class)
    ->names('admin.sites');

//Affectation
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('affectation_listes', AffectationListeController::class)->except(['show', 'edit', 'update']);
});

//presence
Route::middleware(['auth'])->prefix('secretaire')->name('secretaire.')->group(function () {
    Route::get('/presences', [\App\Http\Controllers\Secretaire\PresenceJournaliereController::class, 'index'])->name('presences.index');
    Route::post('/presences', [\App\Http\Controllers\Secretaire\PresenceJournaliereController::class, 'store'])->name('presences.store');
});

//manager

    Route::middleware(['auth'])->group(function () {
        Route::prefix('manager')->name('manager.')->group(function () {
            Route::get('/presences', [PresenceValidationController::class, 'index'])->name('presences.index');
            Route::post('/presences/validate', [PresenceValidationController::class, 'validateAll'])->name('presences.validate');
        });
    });
    

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/user', [UserController::class, 'index'])->name('users.index');
        Route::post('/user', [UserController::class, 'store'])->name('users.store');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

require __DIR__.'/auth.php';