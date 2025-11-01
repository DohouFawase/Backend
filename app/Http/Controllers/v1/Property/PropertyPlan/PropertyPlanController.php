<?php

namespace App\Http\Controllers\v1\Property\PropertyPlan;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\PlanRepository;
use App\Http\Requests\Planproperty\CreatePlanFormRequest; // Notre FormRequest de création
use App\Http\Requests\Planproperty\UpdatePlanFormRequest; // Nous allons la créer ensuite
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdVersionController extends Controller
{
    protected $planRepository;

    public function __construct(PlanRepository $planRepository)
    {
       $this->planRepository = $planRepository;
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
         
$perPage = $request->get('per_page', 0); // 0 pour tout récupérer par défaut
        
        $plans = $this->planRepository->getAll((int)$perPage);

            return api_response(true, 'Liste des versions récupérée avec succès.', [
                'plan' => $plans,
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des versions.', [
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
    public function show(string $planID): JsonResponse
    {
        try {
            $plan = $this->planRepository->findById($planID);

            if (!$plan) {
                return api_response(false, 'plan non trouvée.', null, 404);
            }

            return api_response(true, 'Détails de la plan récupérés avec succès.', [
                'plan' => $plan
            ], 200);

        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération de la plan.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une nouvelle version d'annonce ou une modification d'annonce existante.
     * 
     * @param CreatePlanFormRequest $request Les données validées
     * @param string|null $adId L'ID de l'annonce mère si c'est une modification
     * @return JsonResponse
     */
    public function store(CreatePlanFormRequest $request): JsonResponse
    {
   
        try {
           $data = $request->validated();

           $plan = $this->planRepository->create($data);

           if(!$plan) {
            return api_response(false, '', 500);
           }

            return api_response(true, 'Plan créé avec succès.', [
                'plan' => $plan,
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
     * @param UpdatePlanFormRequest $request
     * @param string $versionId
     * @return JsonResponse
     */
    public function update(UpdatePlanFormRequest $request, string $planID): JsonResponse
    {
        try {
            $data = $request->validated();
            $plan = $this->planRepository->update($planID, $data);
            
            if (!$plan) {
                return api_response(false, "Échec de la mise à jour de la version. Seules les versions 'draft' peuvent être modifiées.", null, 422);
            }

            return api_response(true, 'Plan mis à jour avec succès.', [
                'plan' => $plan
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
    public function destroy(string $planID): JsonResponse
    {
        try {
            $deleted = $this->planRepository->delete($planID);

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


}