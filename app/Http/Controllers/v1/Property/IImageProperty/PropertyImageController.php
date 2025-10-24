<?php

namespace App\Http\Controllers\v1\Property\IImageProperty;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\PropertyImageRepository;
use App\Http\Requests\ImageProperty\CreateImagePropertyFormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PropertyImageController extends Controller
{
    //
    protected PropertyImageRepository $imageRepository;
    
    public function __construct(PropertyImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    /**
     * Upload d'images pour une AdVersion existante.
     * 
     * POST /api/ad-versions/{adVersionId}/images
     */
    public function store(CreateImagePropertyFormRequest $request): JsonResponse
    {
        try {
            $adVersion = $request->getAdVersion();
            
            // Vérifier que l'utilisateur a le droit de modifier cette version
            // (À adapter selon ta logique d'autorisation)
            if ($adVersion->ad->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas autorisé à modifier cette annonce.'
                ], 403);
            }

            // Upload et association des images
            $uploadedImages = $this->imageRepository->uploadAndAttachImages(
                $adVersion,
                $request->file('images'),
                $request->input('main_image_index', 0)
            );

            return response()->json([
                'message' => 'Images uploadées avec succès.',
                'data' => [
                    'ad_version_id' => $adVersion->id,
                    'images' => $uploadedImages->map(fn($img) => [
                        'id' => $img->id,
                        'filename' => $img->filename,
                        'url' => asset('storage/' . $img->filepath),
                        'is_main' => $img->pivot->is_main ?? false
                    ])
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'upload des images.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer l'image principale d'une AdVersion.
     * 
     * PATCH /api/ad-versions/{adVersionId}/images/{imageId}/set-main
     */
    public function setMainImage(string $adVersionId, string $imageId): JsonResponse
    {
        try {
            $adVersion = \App\Models\AdVersion::findOrFail($adVersionId);

            // Vérification des droits
            if ($adVersion->ad->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Non autorisé.'
                ], 403);
            }

            // Vérifier que l'image appartient bien à cette version
            if (!$adVersion->images()->where('property_images.id', $imageId)->exists()) {
                return response()->json([
                    'message' => 'Cette image n\'appartient pas à cette version.'
                ], 404);
            }

            $this->imageRepository->changeMainImage($adVersion, $imageId);

            return response()->json([
                'message' => 'Image principale mise à jour avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une image d'une AdVersion.
     * 
     * DELETE /api/ad-versions/{adVersionId}/images/{imageId}
     */
    public function destroy(string $adVersionId, string $imageId): JsonResponse
    {
        try {
            $adVersion = \App\Models\AdVersion::findOrFail($adVersionId);

            // Vérification des droits
            if ($adVersion->ad->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Non autorisé.'
                ], 403);
            }

            // Vérifier qu'il reste au moins 2 images (sinon on ne peut pas supprimer)
            if ($adVersion->images()->count() <= 1) {
                return response()->json([
                    'message' => 'Impossible de supprimer la dernière image.'
                ], 422);
            }

            $this->imageRepository->detachImage($adVersion, $imageId);

            return response()->json([
                'message' => 'Image supprimée avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer toutes les images d'une AdVersion.
     * 
     * GET /api/ad-versions/{adVersionId}/images
     */
    public function index(string $adVersionId): JsonResponse
    {
        try {
            $adVersion = \App\Models\AdVersion::findOrFail($adVersionId);
            
            $images = $this->imageRepository->getImagesForAdVersion($adVersion);

            return response()->json([
                'data' => $images->map(fn($img) => [
                    'id' => $img->id,
                    'filename' => $img->filename,
                    'url' => asset('storage/' . $img->filepath),
                    'is_main' => $img->pivot->is_main,
                    'uploaded_at' => $img->created_at->toDateTimeString()
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des images.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
