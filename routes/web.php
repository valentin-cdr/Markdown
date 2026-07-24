<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocumentController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\ConfigurationController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. La route directe pour le bouton du Projet A
Route::get('/direct-home/{group}', [HomeController::class, 'index'])->name('direct.home');

// 2. La route interne pour le menu déroulant du Projet B
Route::get('/switch-environment/{group}', function ($group) {
    if ($group === 'retd' || $group === 'global') {
        // 🚀 SI C'EST LE RÉSEAU GLOBAL : On force 'retd' dans toutes les clés de session
        session(['forced_group_key' => 'retd']);
        session(['active_group_key' => 'retd']);
        session(['admin_forced_group' => 'retd']);
        session()->forget('simulated_group_id');
        session()->forget('simulated_franchise_id');
    } else {
        // 🔍 SINON : On cherche le vrai groupe dans la BDD
        $groupeModel = \App\Models\Group::where('key', $group)->first() ?? \App\Models\Group::where('slug', $group)->first();
        
        if ($groupeModel) {
            $trueKey = $groupeModel->key;
            session(['forced_group_key' => $trueKey]);
            session(['active_group_key' => $trueKey]);
            session(['admin_forced_group' => $trueKey]);
            session(['simulated_group_id' => $groupeModel->id]);
            session(['simulated_franchise_id' => $groupeModel->id]);
        }
    }
    
    session()->save();
    
    return redirect()->route('home');
})->name('env.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'redirectToKeycloak'])->name('login');
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
    
    // Route pour permettre à l'admin de changer d'environnement/groupe à la volée
    Route::get('/groups/switch/{key}', [ConfigurationController::class, 'switchGroup'])->name('groups.switch');

});