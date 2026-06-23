<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocumentController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\ConfigurationController;

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
    Route::get('/', fn() => redirect()->route('home'));
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('settings.index');
    Route::post('/configuration/groups', [ConfigurationController::class, 'store'])->name('settings.groups.store');
    Route::put('/configuration/groups/{group}', [ConfigurationController::class, 'update'])->name('settings.groups.update');
    Route::delete('/configuration/groups/{group}', [ConfigurationController::class, 'destroy'])->name('settings.groups.destroy');    
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