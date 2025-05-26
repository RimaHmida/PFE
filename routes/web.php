<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AffectationController;
use App\Http\Controllers\Admin\EmployeController;
use App\Http\Controllers\Admin\SiteController;

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
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::resource('affectations', AffectationController::class)->except(['edit', 'update', 'show']);
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