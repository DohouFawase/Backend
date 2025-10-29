<?php

namespace App\Http\Repositories\Property;

use App\Models\PropertyImage;
use App\Models\AdVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyImageRepository
{
    /**
     * 🆕 Stocke UNE image physiquement et crée l'enregistrement en DB.
     * Utilisé pour l'upload temporaire AVANT la création d'AdVersion.
     *
     * @param UploadedFile $file
     * @param string $userId
     * @return PropertyImage
     */
    public function storeImage(UploadedFile $file, string $userId): PropertyImage
    {
        // Génération d'un nom unique
        $filename = $this->generateUniqueFilename($file);

        // Stockage du fichier
        $filepath = Storage::disk('public')->putFileAs(
            'properties/images',
            $file,
            $filename
        );

        // Création de l'enregistrement en base
        return PropertyImage::create([
            'id' => Str::uuid()->toString(),
            'filename' => $filename,
            'filepath' => $filepath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'user_id' => $userId
        ]);
    }

    /**
     * Génère un nom de fichier unique pour éviter les collisions.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * 🆕 Associe des images existantes à une AdVersion.
     * Utilisé après la création de l'AdVersion.
     *
     * @param AdVersion $adVersion
     * @param array $imageIds Array d'UUIDs
     * @param string $mainImageId UUID de l'image principale
     * @return void
     */
    public function attachImagesToVersion(AdVersion $adVersion, array $imageIds, string $mainImageId): void
    {
        DB::beginTransaction();
        try {
            // Préparer les données du pivot
            $attachments = [];
            foreach ($imageIds as $id) {
                $attachments[$id] = ['is_main' => ($id === $mainImageId)];
            }

            // Synchroniser les images
            $adVersion->images()->sync($attachments);

            // Mettre à jour photos_json (pour compatibilité legacy)
            $adVersion->update(['photos_json' => $imageIds]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Change l'image principale d'une AdVersion.
     */
    public function changeMainImage(AdVersion $adVersion, string $newMainImageId): void
    {
        DB::beginTransaction();
        try {
            // Retirer is_main de toutes les images
            $allImageIds = $adVersion->images()->pluck('property_images.id')->toArray();
            foreach ($allImageIds as $id) {
                $adVersion->images()->updateExistingPivot($id, ['is_main' => false]);
            }

            // Activer is_main pour la nouvelle image
            $adVersion->images()->updateExistingPivot($newMainImageId, ['is_main' => true]);

            // Mettre à jour main_photo_filename
            $newMainImage = PropertyImage::findOrFail($newMainImageId);
            $adVersion->update(['main_photo_filename' => $newMainImage->filename]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Supprime une image d'une AdVersion.
     */
    public function detachImage(AdVersion $adVersion, string $imageId): void
    {
        DB::beginTransaction();
        try {
            $image = PropertyImage::findOrFail($imageId);

            // Détacher de l'AdVersion
            $adVersion->images()->detach($imageId);

            // Si l'image n'est plus utilisée, la supprimer
            if ($image->adVersions()->count() === 0) {
                $this->deleteImageFile($image);
                $image->delete();
            }

            // Mettre à jour photos_json
            $imageIds = $adVersion->images()->pluck('property_images.id')->toArray();
            $adVersion->update(['photos_json' => $imageIds]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Supprime physiquement le fichier.
     */
    private function deleteImageFile(PropertyImage $image): void
    {
        if (Storage::disk('public')->exists($image->filepath)) {
            Storage::disk('public')->delete($image->filepath);
        }
    }

    /**
     * Récupère toutes les images d'une AdVersion.
     */
    public function getImagesForAdVersion(AdVersion $adVersion): \Illuminate\Support\Collection
    {
        return $adVersion->images()
            ->withPivot('is_main')
            ->orderByPivot('is_main', 'desc')
            ->get();
    }

    /**
     * Récupère l'image principale.
     */
    public function getMainImage(AdVersion $adVersion): ?PropertyImage
    {
        return $adVersion->images()
            ->wherePivot('is_main', true)
            ->first();
    }

    /**
     * Nettoie les images orphelines (non associées).
     */
    public function cleanOrphanImages(int $hoursOld = 24): int
    {
        $orphanImages = PropertyImage::doesntHave('adVersions')
            ->where('created_at', '<', now()->subHours($hoursOld))
            ->get();

        $count = 0;
        foreach ($orphanImages as $image) {
            $this->deleteImageFile($image);
            $image->delete();
            $count++;
        }

        return $count;
    }
}