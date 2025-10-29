<?php

namespace App\Http\Controllers\v1\Property\IImageProperty;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\PropertyImageRepository;
use App\Http\Requests\ImageProperty\CreateImagePropertyFormRequest;
use App\Http\Requests\ImageProperty\UploadTemporaryImagesRequest;
use Illuminate\Http\JsonResponse;

class PropertyImageController extends Controller
{
    protected PropertyImageRepository $imageRepository;
    
    public function __construct(PropertyImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    /**
     * 🎯 ÉTAPE 1 : Upload temporaire d'images AVANT la création de l'AdVersion.
     * 
     * POST /api/images/upload-temporary
     * 
     * Body (Multipart Form):
     *   - images[] : fichier image (min 1, max 10)
     */
    public function uploadTemporary(CreateImagePropertyFormRequest $request): JsonResponse
    {
        try {
            $uploadedImages = [];

            // Upload chaque image une par une
            foreach ($request->file('images') as $file) {
                $image = $this->imageRepository->storeImage($file, auth()->id());
                $uploadedImages[] = $image;
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploadées avec succès. Vous pouvez maintenant créer votre annonce.',
                'data' => [
                    'images' => collect($uploadedImages)->map(fn($img) => [
                        'id' => $img->id,
                        'filename' => $img->filename,
                        'url' => asset('storage/' . $img->filepath),
                        'size' => $img->file_size,
                        'mime_type' => $img->mime_type
                    ])
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erreur upload images temporaires: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }

            // Vérifier que l'image appartient à cette version
            if (!$adVersion->images()->where('property_images.id', $imageId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette image n\'appartient pas à cette version.'
                ], 404);
            }

            $this->imageRepository->changeMainImage($adVersion, $imageId);

            return response()->json([
                'success' => true,
                'message' => 'Image principale mise à jour avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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

            if ($adVersion->ad->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }

            // Vérifier qu'il reste au moins 2 images
            if ($adVersion->images()->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer la dernière image.'
                ], 422);
            }

            $this->imageRepository->detachImage($adVersion, $imageId);

            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => true,
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
                'success' => false,
                'message' => 'Erreur lors de la récupération des images.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}