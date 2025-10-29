<?php

use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\v1\Property\Equipement\EquipementCategoryController;
use App\Http\Controllers\v1\Property\Equipement\EquipementController;
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


Route::resource('equipmentscategory', EquipementCategoryController::class);
Route::resource('equipments', EquipementController::class);
// ->only([
//     'index', 'show'
// ]);×