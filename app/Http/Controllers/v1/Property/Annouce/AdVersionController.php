<?php

namespace App\Http\Controllers\v1\Property\Annouce;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\AdVersionRepository;
use App\Http\Requests\Anounce\CreateAnnouceFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable; // Ajout de Throwable pour la gestion d'erreurs

class AdVersionController extends Controller
{
    protected $adVersionRepository;

    public function __construct(AdVersionRepository $adVersionRepository)
    {
        
        $this->adVersionRepository = $adVersionRepository;
    }

    public function index(Request $request)
    {
        // ... (Logique pour lister les versions d'annonces) ...
         $filters = $request->only(['status', 'ad_id', 'property_type_id']);
    $perPage = $request->input('per_page', 15);
    
    $versions = $this->adVersionRepository->getAll($perPage, $filters);
    return $versions;
    }


   /**
    * Crée une nouvelle version d'annonce ou une modification d'annonce existante.
    * * @param CreateAnnouceFormRequest $request Les données validées.
    * @param string|null $adId L'ID de l'annonce mère si c'est une modification.
    * @return JsonResponse
    */
    public function store(CreateAnnouceFormRequest $request, ?string $adId = null): JsonResponse
    {
        // On récupère l'ID de l'utilisateur authentifié (sécurité)
        $userId = auth()->id();

        try {
            $newVersion = $this->adVersionRepository->createVersion(
                $request->validated(), 
                $userId, 
                $adId
            );

            if (!$newVersion) {
                // Le Repository a logué l'erreur de transaction.
                return api_response(false, "Échec de la création de la version d'annonce. Veuillez réessayer.", null, 500);
            }

            $message = $adId 
                ? "Nouvelle version d'annonce soumise pour validation."
                : "Annonce créée avec succès ! Elle est en attente de validation.";

            return api_response(true, $message, [
                'ad_id' => $newVersion->ad_id,
                'version_id' => $newVersion->id,
            ], 201); // 201 Created
            
        } catch (Throwable $e) {
            // Cette erreur catch les problèmes non gérés (ex: problème de connexion DB avant la transaction)
            return api_response(false, 'Une erreur interne est survenue lors du traitement de l\'annonce.', [
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }
}