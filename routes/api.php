<?php

use App\Http\Controllers\Api\DocumentApiController;
use Illuminate\Support\Facades\Route;

// 🛡️ Le vigile à l'entrée : il faut une clé pour passer !
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/documents', [DocumentApiController::class, 'index']);
    Route::get('/documents/{id}', [DocumentApiController::class, 'show']);

});

// 1|MpCFvB9yNVG7CDQoHLurbnjQpKM7OPUFegHJosDa78329be1