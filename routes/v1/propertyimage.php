<?php

use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\v1\Property\Annouce\AdVersionController;
use App\Http\Controllers\v1\Property\IImageProperty\PropertyImageController;
use App\Http\Controllers\v1\Property\PropertyType\PropertyTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::middleware('auth:sanctum')->group(function () {
//     // Équipements
//     Route::get('/equipments', [\App\Http\Controllers\EquipmentController::class, 'index']);
//     Route::post('/equipments', [\App\Http\Controllers\EquipmentController::class, 'store']);
//     Route::get('/equipments/{id}', [\App\Http\Controllers\EquipmentController::class, 'show']);
//     Route::put('/equipments/{id}', [\App\Http\Controllers\EquipmentController::class, 'update']);
//     Route::delete('/equipments/{id}', [\App\Http\Controllers\EquipmentController::class, 'destroy']);
// });


// Route::resource('propertieimages', PropertyImageController::class)->middleware('auth:sanctum')->except([
// 'index', 'show'
// ]);
// ->only([
//     'index', 'show'
// ]);×


// Route::resource('adversions', AdVersionController::class);
// ->only([
//     'index', 'show'
// ]);



Route::middleware('auth:sanctum')->group(function () {
    
    // ✅ Upload temporaire (AVANT création AdVersion)
    Route::post('images/upload-temporary', [PropertyImageController::class, 'uploadTemporary']);
    
    // Gestion des images d'une AdVersion
    Route::prefix('ad-versions/{adVersionId}/images')->group(function () {
        Route::get('/', [PropertyImageController::class, 'index']);
        Route::post('/', [PropertyImageController::class, 'store']);
        Route::patch('/{imageId}/set-main', [PropertyImageController::class, 'setMainImage']);
        Route::delete('/{imageId}', [PropertyImageController::class, 'destroy']);
    });
});