<?php

namespace App\Http\Controllers\v1\Property\Annouce;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\AdVersionRepository;
use App\Http\Requests\Anounce\CreateAnnouceFormRequest;
use App\Http\Requests\Anounce\UpdateAnnouceFormRequest;
use App\Http\Requests\Anounce\SearchRentalFormRequest;
use App\Http\Requests\Anounce\SearchPropertyFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdVersionController extends Controller
{
    protected $adVersionRepository;

    public function __construct(AdVersionRepository $adVersionRepository)
    {
        $this->adVersionRepository = $adVersionRepository;
    }

    /**
     * Liste toutes les versions d'annonces avec filtres.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validation des filtres
            $request->validate([
                'status' => 'nullable|in:draft,pending,validated,rejected',
                'ad_type' => 'nullable|in:for_rent,for_sale',
                'property_type_id' => 'nullable|uuid',
                'ad_id' => 'nullable|uuid',
                'city' => 'nullable|string|max:100',
                'neighborhood' => 'nullable|string|max:100',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'min_bedrooms' => 'nullable|integer|min:0',
                'min_area' => 'nullable|numeric|min:0',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $filters = $request->only([
                'status', 
                'ad_id', 
                'property_type_id',
                'ad_type',
                'city',
                'neighborhood',
                'min_price',
                'max_price',
                'min_bedrooms',
                'min_area'
            ]);
            
            $perPage = $request->input('per_page', 15);
            
            $versions = $this->adVersionRepository->getAll($perPage, $filters);

            return api_response(true, 'Liste des versions récupérée avec succès.', [
                'versions' => $versions,
                'filters_applied' => array_filter($filters) // Affiche les filtres actifs
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des versions.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche d'annonces À LOUER avec critères avancés.
     * 
     * @param SearchRentalFormRequest $request
     * @return JsonResponse
     */
    public function searchForRent(SearchRentalFormRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();

            // Force le type à "for_rent" et status "validated"
            $filters['ad_type'] = 'for_rent';
            $filters['status'] = 'validated';

            $perPage = $request->input('per_page', 15);
            
            $versions = $this->adVersionRepository->search($perPage, $filters);

            return api_response(true, 'Annonces de location récupérées avec succès.', [
                'rentals' => $versions,
                'search_criteria' => array_filter($filters)
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la recherche des locations.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche d'annonces À VENDRE avec critères avancés.
     * 
     * @param SearchPropertyFormRequest $request
     * @return JsonResponse
     */
    public function searchForSale(SearchPropertyFormRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();

            // Force le type à "for_sale" et status "validated"
            $filters['ad_type'] = 'for_sale';
            $filters['status'] = 'validated';

            $perPage = $request->input('per_page', 15);
            
            $versions = $this->adVersionRepository->search($perPage, $filters);

            return api_response(true, 'Annonces de vente récupérées avec succès.', [
                'properties' => $versions,
                'search_criteria' => array_filter($filters)
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la recherche des ventes.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche les détails d'une version spécifique.
     * 
     * @param string $versionId
     * @return JsonResponse
     */
    public function show(string $versionId): JsonResponse
    {
        try {
            $version = $this->adVersionRepository->findById($versionId);

            if (!$version) {
                return api_response(false, 'Version non trouvée.', null, 404);
            }

            return api_response(true, 'Détails de la version récupérés avec succès.', [
                'version' => $version
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération de la version.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une nouvelle version d'annonce ou une modification d'annonce existante.
     * 
     * @param CreateAnnouceFormRequest $request Les données validées
     * @param string|null $adId L'ID de l'annonce mère si c'est une modification
     * @return JsonResponse
     */
    public function store(CreateAnnouceFormRequest $request, ?string $adId = null): JsonResponse
    {
        // Récupération de l'ID de l'utilisateur authentifié (sécurité)
        $userId = auth()->id();

        try {
            $newVersion = $this->adVersionRepository->createVersion(
                $request->validated(),
                $userId,
                $adId
            );

            if (!$newVersion) {
                return api_response(false, "Échec de la création de la version d'annonce. Veuillez réessayer.", null, 500);
            }

            $message = $adId
                ? "Nouvelle version d'annonce soumise pour validation."
                : "Annonce créée avec succès ! Elle est en attente de validation.";

            return api_response(true, $message, [
                'ad_id' => $newVersion->ad_id,
                'version_id' => $newVersion->id,
                'version' => $newVersion
            ], 201);

        } catch (Throwable $e) {
            return api_response(false, 'Une erreur interne est survenue lors du traitement de l\'annonce.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une version existante (seulement si status = draft).
     * 
     * @param UpdateAnnouceFormRequest $request
     * @param string $versionId
     * @return JsonResponse
     */
    public function update(UpdateAnnouceFormRequest $request, string $versionId): JsonResponse
    {
        try {
            $updatedVersion = $this->adVersionRepository->update(
                $versionId,
                $request->validated()
            );

            if (!$updatedVersion) {
                return api_response(false, "Échec de la mise à jour de la version. Seules les versions 'draft' peuvent être modifiées.", null, 422);
            }

            return api_response(true, 'Version mise à jour avec succès.', [
                'version_id' => $updatedVersion->id,
                'version' => $updatedVersion
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la mise à jour.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une version d'annonce.
     * 
     * @param string $versionId
     * @return JsonResponse
     */
    public function destroy(string $versionId): JsonResponse
    {
        try {
            $deleted = $this->adVersionRepository->delete($versionId);

            if (!$deleted) {
                return api_response(false, 'Impossible de supprimer cette version. Elle est peut-être active ou n\'existe pas.', null, 422);
            }

            return api_response(true, 'Version supprimée avec succès.', null, 200);

        } catch (Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la suppression.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste toutes les versions d'une annonce spécifique.
     * 
     * @param string $adId
     * @return JsonResponse
     */
    public function getByAd(string $adId): JsonResponse
    {
        try {
            $versions = $this->adVersionRepository->getAllByAd($adId);

            return api_response(true, 'Versions de l\'annonce récupérées avec succès.', [
                'ad_id' => $adId,
                'versions' => $versions
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des versions.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les versions actives (validées).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $versions = $this->adVersionRepository->getAllActive($perPage);

            return api_response(true, 'Versions actives récupérées avec succès.', [
                'versions' => $versions
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des versions actives.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les versions avec compteurs d'équipements et images.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithCounts(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $versions = $this->adVersionRepository->getAllWithCounts($perPage);

            return api_response(true, 'Versions avec compteurs récupérées avec succès.', [
                'versions' => $versions
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des versions.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }


/**
 * Valide une version d'annonce (ADMIN UNIQUEMENT).
 * 
 * @param string $versionId
 * @return JsonResponse
 */
public function validate(string $versionId): JsonResponse
{
    try {
        $adminId = auth()->id();
        
        $version = $this->adVersionRepository->validateVersion($versionId, $adminId);
        
        if (!$version) {
            return api_response(false, 'Impossible de valider cette version. Elle doit être en statut "pending".', null, 422);
        }
        
        return api_response(true, 'Version validée avec succès.', [
            'version_id' => $version->id,
            'version' => $version
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Une erreur est survenue lors de la validation.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}

/**
 * Liste toutes les versions en attente de validation (ADMIN UNIQUEMENT).
 * 
 * @param Request $request
 * @return JsonResponse
 */
public function getPending(Request $request): JsonResponse
{
    try {
        $perPage = $request->input('per_page', 15);
        
        $versions = $this->adVersionRepository->getAllPending($perPage);
        
        return api_response(true, 'Versions en attente récupérées avec succès.', [
            'pending_versions' => $versions,
            'total_pending' => $versions->total()
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Erreur lors de la récupération des versions en attente.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}



/**
 * Liste toutes les versions validées (ADMIN).
 * 
 * @param Request $request
 * @return JsonResponse
 */
public function getValidated(Request $request): JsonResponse
{
    try {
        $perPage = $request->input('per_page', 15);
        
        $versions = $this->adVersionRepository->getAllValidated($perPage);
        
        return api_response(true, 'Versions validées récupérées avec succès.', [
            'validated_versions' => $versions,
            'total_validated' => $versions->total()
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Erreur lors de la récupération des versions validées.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}



/**
 * Active une version validée (la rend publique) - ADMIN UNIQUEMENT.
 * 
 * @param string $versionId
 * @return JsonResponse
 */
public function activate(string $versionId): JsonResponse
{
    try {
        $version = $this->adVersionRepository->activateVersion($versionId);
        
        if (!$version) {
            return api_response(false, 'Impossible d\'activer cette version. Elle doit être validée d\'abord.', null, 422);
        }
        
        return api_response(true, 'Version activée et publiée avec succès.', [
            'version_id' => $version->id,
            'ad_id' => $version->ad_id,
            'version' => $version
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Une erreur est survenue lors de l\'activation.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}

/**
 * Liste toutes les versions refusées (ADMIN).
 * 
 * @param Request $request
 * @return JsonResponse
 */
public function getRefused(Request $request): JsonResponse
{
    try {
        $perPage = $request->input('per_page', 15);
        
        $versions = $this->adVersionRepository->getAllRefused($perPage);
        
        return api_response(true, 'Versions refusées récupérées avec succès.', [
            'refused_versions' => $versions,
            'total_refused' => $versions->total()
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Erreur lors de la récupération des versions refusées.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}



/**
 * Statistiques globales pour le dashboard admin.
 * 
 * @return JsonResponse
 */
public function getAdminStats(): JsonResponse
{
    try {
        $stats = $this->adVersionRepository->getAdminStats();
        
        return api_response(true, 'Statistiques admin récupérées avec succès.', [
            'stats' => $stats
        ], 200);
        
    } catch (Throwable $e) {
        return api_response(false, 'Erreur lors de la récupération des statistiques.', [
            'error_detail' => $e->getMessage()
        ], 500);
    }
}


}