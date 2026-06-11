<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocumentController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/auth/keycloak/redirect', [LoginController::class, 'redirectToKeycloak'])->name('keycloak.login');
    Route::get('/auth/keycloak/callback', [LoginController::class, 'handleKeycloakCallback']);
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Éditeur
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    
    // CRUD Base de données
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Lecture seule
    Route::get('/documents/{document}/show', [DocumentController::class, 'show'])->name('documents.show');

    // Partage
    Route::get('/documents/{document}/share', [DocumentController::class, 'shareForm'])->name('documents.share.form');
    Route::post('/documents/{document}/share', [DocumentController::class, 'share'])->name('documents.share');
    Route::patch('/documents/{document}/share/{user}', [DocumentController::class, 'updateShare'])->name('documents.share.update');
    
    Route::delete('/documents/{document}/share/{user}', [DocumentController::class, 'unshare'])->name('documents.unshare');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| ROUTES DE DÉVELOPPEMENT (Uniquement en local)
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    
    // 👤 PREMIER COMPTE TESTEUR
    Route::get('/dev/login-test', function () {
        $testUser = \App\Models\User::firstOrCreate(
            ['username' => 'test_invite'],
            [
                'name' => 'Compte Testeur 1',
                'email' => 'testeur1@local.dev',
                'password' => bcrypt(\Illuminate\Support\Str::random(24)),
            ]
        );

        Auth::login($testUser);
        
        // 🚀 INJECTION DU GROUPE EN SESSION
        session(['keycloak_groups' => ['test', 'testeur']]);
        
        return redirect('/home');
    });

    // 👥 DEUXIÈME COMPTE TESTEUR
    Route::get('/dev/login-test2', function () {
        $testUser = \App\Models\User::firstOrCreate(
            ['username' => 'test_invite_2'],
            [
                'name' => 'Compte Testeur 2',
                'email' => 'testeur2@local.dev',
                'password' => bcrypt(\Illuminate\Support\Str::random(24)),
            ]
        );

        Auth::login($testUser);
        
        // 🚀 INJECTION D'UN AUTRE GROUPE EN SESSION
        session(['keycloak_groups' => ['marketing', 'visiteur']]);
        
        return redirect('/home');
    });
}