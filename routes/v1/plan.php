<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Property\PropertyPlan\PropertyPlanController;


Route::resource('plan', PropertyPlanController::class);