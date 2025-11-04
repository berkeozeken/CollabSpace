<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Teams
    Route::get('/teams',            [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams',           [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}',     [TeamController::class, 'show'])->name('teams.show');
    Route::patch('/teams/{team}',   [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}',  [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/transfer', [TeamController::class, 'transferOwnership'])->name('teams.transfer');

    // Team members
    Route::post('/teams/{team}/members',              [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::delete('/teams/{team}/members/{user}',     [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
    Route::patch('/teams/{team}/members/{user}/role', [TeamMemberController::class, 'updateRole'])->name('teams.members.role');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Auth routes (Breeze)
|--------------------------------------------------------------------------
| BUNU KESİNLİKLE EKLE: login/register/verify + LOGOUT burada!
*/
require __DIR__.'/auth.php';
