<?php

use App\Http\Controllers\v1\User\UserController;
use Illuminate\Support\Facades\Route;


Route::resource('/user', UserController::class) ->except([
    'store'
]);


Route::get('users/archived', [UserController::class, 'showDeactivatedUsers']);

    // B. Désactiver un utilisateur (Soft Delete)
    // Utilise DELETE car c'est une forme de suppression/archivage.
    Route::delete('users/{id}/deactivate', [UserController::class, 'deactivateUser']);

    // C. Réactiver (Restaurer) un utilisateur archivé
    // Utilise POST ou PUT/PATCH car cela modifie l'état de la ressource.
    Route::post('users/{id}/reactivate', [UserController::class, 'reactivateUser']);