<?php

use App\Http\Controllers\v1\Property\Annouce\AdVersionController;
use App\Http\Controllers\v1\Property\PropertyContact\PropertyContactController;
use Illuminate\Support\Facades\Route;


Route::resource('adversions', AdVersionController::class)->middleware('auth:sanctum')->except([
'index', 'show'
]);


//     // Routes spécifiques
//     Route::get('/ads/{adId}/adversions', [AdVersionController::class, 'getByAd']);
//     Route::get('/adversions/active', [AdVersionController::class, 'getActive']);
//     Route::get('/adversions/counts', [AdVersionController::class, 'getWithCounts']);
//     Route::post('/ads/{adVersionId}/contact', [PropertyContactController::class, 'store']);
//     Route::middleware('auth:sanctum')->group(function () {
      
//       // Récupérer la liste des messages reçus par le propriétaire connecté
//       Route::get('/owner/property-contacts', [PropertyContactController::class, 'index']);
      
//       // Statistiques des messages pour le propriétaire connecté
//       Route::get('/owner/property-contacts/stats', [PropertyContactController::class, 'stats']);
      
//     // Afficher un message spécifique (et le marque comme lu)
//     Route::get('/owner/property-contacts/{contactId}', [PropertyContactController::class, 'show']);

//     // Marquer un message comme répondu
//     Route::patch('/owner/property-contacts/{contactId}/mark-replied', [PropertyContactController::class, 'markAsReplied']);

//     // Archiver un message
//     Route::patch('/owner/property-contacts/{contactId}/archive', [PropertyContactController::class, 'archive']);

//     // Supprimer un message
//     Route::delete('/owner/property-contacts/{contactId}', [PropertyContactController::class, 'destroy']);
    
// });
/*
|--------------------------------------------------------------------------
| 🌐 ROUTES PUBLIQUES - Consultation des annonces
|--------------------------------------------------------------------------
| Accessibles sans authentification
*/

// 🏠 Recherche de LOCATIONS (for_rent)
Route::get('/rentals/search', [AdVersionController::class, 'searchForRent'])
    ->name('rentals.search');

// 🏡 Recherche de VENTES (for_sale)
Route::get('/properties/search', [AdVersionController::class, 'searchForSale'])
    ->name('properties.search');

// 📋 Détails d'une annonce publique (version validée)
Route::get('/adversions/{id}', [AdVersionController::class, 'show'])
    ->name('adversions.show');

// 📊 Liste des annonces actives (validées et publiées)
Route::get('/adversions/active', [AdVersionController::class, 'getActive'])
    ->name('adversions.active');

// 📧 Contacter le propriétaire d'une annonce (PUBLIC)
Route::post('/ads/{adVersionId}/contact', [PropertyContactController::class, 'store'])
    ->name('property.contact.store');

/*
|--------------------------------------------------------------------------
| 🔐 ROUTES AUTHENTIFIÉES - Gestion utilisateur
|--------------------------------------------------------------------------
| Nécessitent une authentification (auth:sanctum)
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // ========================================
    // 📝 CRUD DES ANNONCES
    // ========================================
    
    // Créer une nouvelle annonce
    Route::post('/adversions', [AdVersionController::class, 'store'])
        ->name('adversions.store');
    
    // Créer une modification d'annonce existante (nouvelle version)
    Route::post('/ads/{adId}/adversions', [AdVersionController::class, 'store'])
        ->name('adversions.store.modification');
    
    // Mettre à jour une version (seulement draft)
    Route::put('/adversions/{id}', [AdVersionController::class, 'update'])
        ->name('adversions.update');
    
    Route::patch('/adversions/{id}', [AdVersionController::class, 'update'])
        ->name('adversions.patch');
    
    // Supprimer une version
    Route::delete('/adversions/{id}', [AdVersionController::class, 'destroy'])
        ->name('adversions.destroy');
    
    // ========================================
    // 📂 CONSULTATION DES ANNONCES UTILISATEUR
    // ========================================
    
    // Liste TOUTES les versions avec filtres (mes annonces)
    Route::get('/adversions', [AdVersionController::class, 'index'])
        ->name('adversions.index');
    
    // Toutes les versions d'une annonce spécifique
    Route::get('/ads/{adId}/adversions', [AdVersionController::class, 'getByAd'])
        ->name('adversions.by_ad');
    
    // Versions avec compteurs (équipements, images)
    Route::get('/adversions/counts', [AdVersionController::class, 'getWithCounts'])
        ->name('adversions.with_counts');
    
    // ========================================
    // 📬 GESTION DES CONTACTS PROPRIÉTAIRE
    // ========================================
    
    // Récupérer tous les messages reçus
    Route::get('/owner/property-contacts', [PropertyContactController::class, 'index'])
        ->name('owner.contacts.index');
    
    // Statistiques des messages
    Route::get('/owner/property-contacts/stats', [PropertyContactController::class, 'stats'])
        ->name('owner.contacts.stats');
    
    // Afficher un message spécifique (et le marque comme lu)
    Route::get('/owner/property-contacts/{contactId}', [PropertyContactController::class, 'show'])
        ->name('owner.contacts.show');
    
    // Marquer un message comme répondu
    Route::patch('/owner/property-contacts/{contactId}/mark-replied', [PropertyContactController::class, 'markAsReplied'])
        ->name('owner.contacts.mark_replied');
    
    // Archiver un message
    Route::patch('/owner/property-contacts/{contactId}/archive', [PropertyContactController::class, 'archive'])
        ->name('owner.contacts.archive');
    
    // Supprimer un message
    Route::delete('/owner/property-contacts/{contactId}', [PropertyContactController::class, 'destroy'])
        ->name('owner.contacts.destroy');
});

/*
|--------------------------------------------------------------------------
| 👑 ROUTES ADMIN - Gestion et validation
|--------------------------------------------------------------------------
| Nécessitent authentification + rôle admin
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    // ========================================
    // ✅ VALIDATION DES ANNONCES
    // ========================================
    
    // Valider une version (pending → validated)
    Route::post('/versions/{id}/validate', [AdVersionController::class, 'validate'])
        ->name('admin.versions.validate');
    
    // Rejeter une version avec raison (pending → refused)
    Route::post('/versions/{id}/reject', [AdVersionController::class, 'reject'])
        ->name('admin.versions.reject');
    
    // Activer une version validée (validated → published)
    Route::post('/versions/{id}/activate', [AdVersionController::class, 'activate'])
        ->name('admin.versions.activate');
    
    // ========================================
    // 📋 CONSULTATION PAR STATUT
    // ========================================
    
    // Liste des versions EN ATTENTE de validation
    Route::get('/versions/pending', [AdVersionController::class, 'getPending'])
        ->name('admin.versions.pending');
    
    // Liste des versions VALIDÉES
    Route::get('/versions/validated', [AdVersionController::class, 'getValidated'])
        ->name('admin.versions.validated');
    
    // Liste des versions REFUSÉES
    Route::get('/versions/refused', [AdVersionController::class, 'getRefused'])
        ->name('admin.versions.refused');
    
    // ========================================
    // 📊 STATISTIQUES & DASHBOARD
    // ========================================
    
    // Statistiques globales pour dashboard admin
    Route::get('/stats', [AdVersionController::class, 'getAdminStats'])
        ->name('admin.stats');
    
    // Toutes les versions (admin peut tout voir)
    Route::get('/versions', [AdVersionController::class, 'index'])
        ->name('admin.versions.index');
});

/*
|--------------------------------------------------------------------------
| 📝 RÉSUMÉ DES ENDPOINTS
|--------------------------------------------------------------------------
|
| PUBLIC (sans auth):
| ├─ GET    /rentals/search              → Rechercher des locations
| ├─ GET    /properties/search           → Rechercher des ventes
| ├─ GET    /adversions/{id}             → Détails d'une annonce
| ├─ GET    /adversions/active           → Annonces actives
| └─ POST   /ads/{adVersionId}/contact   → Contacter propriétaire
|
| USER (auth:sanctum):
| ├─ POST   /adversions                  → Créer annonce
| ├─ POST   /ads/{adId}/adversions       → Modifier annonce
| ├─ GET    /adversions                  → Mes annonces
| ├─ GET    /ads/{adId}/adversions       → Versions d'une annonce
| ├─ PUT    /adversions/{id}             → Mettre à jour version
| ├─ DELETE /adversions/{id}             → Supprimer version
| ├─ GET    /owner/property-contacts     → Mes messages reçus
| ├─ GET    /owner/property-contacts/{id}→ Voir message
| └─ ...autres routes contacts
|
| ADMIN (auth:sanctum + admin):
| ├─ POST   /admin/versions/{id}/validate   → Valider
| ├─ POST   /admin/versions/{id}/reject     → Rejeter
| ├─ POST   /admin/versions/{id}/activate   → Activer
| ├─ GET    /admin/versions/pending         → Versions en attente
| ├─ GET    /admin/versions/validated       → Versions validées
| ├─ GET    /admin/versions/refused         → Versions refusées
| └─ GET    /admin/stats                    → Statistiques
|
*/