<?php

use App\Http\Controllers\Api\DocumentApiController;
use Illuminate\Support\Facades\Route;

// 🛡️ Le vigile à l'entrée : il faut une clé pour passer !
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/documents/search', [DocumentApiController::class, 'search']);
    Route::get('/documents/group/{groupKey}/search', [DocumentApiController::class, 'searchInGroup']);
    Route::get('/documents/group/{groupKey}', [DocumentApiController::class, 'byGroup']);
    Route::get('/documents', [DocumentApiController::class, 'index']);
    Route::get('/documents/{id}', [DocumentApiController::class, 'show']);
});