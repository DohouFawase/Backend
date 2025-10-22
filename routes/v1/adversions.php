<?php

use App\Http\Controllers\v1\Property\Annouce\AdVersionController;
use Illuminate\Support\Facades\Route;


Route::resource('adversions', AdVersionController::class)->middleware('auth:sanctum')->except([
'index', 'show'
]);
// ->only([
//     
// ]);