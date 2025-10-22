<?php

namespace App\Http\Controllers\v1\Property\Annouce;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\AdVersionRepository;
use App\Http\Requests\Anounce\CreateAnnouceFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class AdVersionController extends Controller
{
    //
    protected $adVersionRepository;

    public function __construct(AdVersionRepository $adVersionRepository)
    {
        // 🎯 Restriction : Seuls les utilisateurs authentifiés peuvent accéder à ce contrôleur
        
        $this->adVersionRepository = $adVersionRepository;
    }

    public function index()
    {
        //
    }


   public function store(CreateAnnouceFormRequest $request, ?string $adId = null): JsonResponse
    {
        // On récupère l'ID de l'utilisateur authentifié (sécurité)
        $userId = auth()->id();

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
        ], 201); // 201 Created
        // try {

        // } catch (\Throwable $e) {
        //     // Cette erreur catch les problèmes non gérés par la transaction du Repository
        //     return api_response(false, 'Une erreur interne est survenue lors du traitement de l\'annonce.', $e->getMessage(), 500);
        // }
    }
}
