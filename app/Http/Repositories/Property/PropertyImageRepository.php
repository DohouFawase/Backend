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
     * Upload multiple images et les associe à une AdVersion.
     *
     * @param AdVersion $adVersion
     * @param array $images Tableau de UploadedFile
     * @param int $mainImageIndex Index de l'image principale (0 par défaut)
     * @return array Tableau des PropertyImage créées
     */
    public function uploadAndAttachImages(
        AdVersion $adVersion,
        array $images,
        int $mainImageIndex = 0
    ): \Illuminate\Support\Collection {
        $uploadedImages = collect(); // ✅ Utiliser une Collection dès le départ

        DB::beginTransaction();
        try {
            foreach ($images as $index => $image) {
                // ÉTAPE 1 : Upload du fichier physique
                $propertyImage = $this->storeImage($image, auth()->id());

                // ÉTAPE 2 : Association à l'AdVersion via la pivot
                $isMain = ($index === $mainImageIndex);
                $adVersion->images()->attach($propertyImage->id, [
                    'is_main' => $isMain
                ]);

                $uploadedImages->push($propertyImage); // ✅ Push dans la collection
            }

            // ÉTAPE 3 : Reload les images avec les données pivot
            $adVersion->load('images');
            
            // ÉTAPE 4 : Récupérer les images avec leurs données pivot
            $uploadedImages = $adVersion->images;

            // ÉTAPE 5 : Mise à jour du champ photos_json (optionnel, pour redondance)
            $this->updatePhotosJson($adVersion);

            // ÉTAPE 6 : Mise à jour du main_photo_filename
            $mainImage = $uploadedImages->firstWhere('pivot.is_main', true);
            if ($mainImage) {
                $adVersion->update([
                    'main_photo_filename' => $mainImage->filename
                ]);
            }

            DB::commit();
            return $uploadedImages;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Nettoyer les fichiers uploadés en cas d'erreur
            foreach ($uploadedImages as $img) {
                $this->deleteImageFile($img);
            }
            
            throw $e;
        }
    }
    /**
     * Stocke physiquement une image et crée l'enregistrement en DB.
     *
     * @param UploadedFile $file
     * @param string $userId
     * @return PropertyImage
     */
    public function storeImage(UploadedFile $file, string $userId): PropertyImage
    {
        // Génération d'un nom unique
        $filename = $this->generateUniqueFilename($file);

        // Stockage du fichier (local ou S3)
        $filepath = Storage::disk('public')->putFileAs(
            'properties/images',
            $file,
            $filename
        );

        // Création de l'enregistrement en base
        return PropertyImage::create([
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
     * Met à jour le champ photos_json de l'AdVersion avec tous les IDs d'images.
     */
    public function updatePhotosJson(AdVersion $adVersion): void
    {
        $imageIds = $adVersion->images()->pluck('property_images.id')->toArray();
        
        $adVersion->update([
            'photos_json' => $imageIds
        ]);
    }

    /**
     * Change l'image principale d'une AdVersion.
     *
     * @param AdVersion $adVersion
     * @param string $newMainImageId UUID de la nouvelle image principale
     */
    public function changeMainImage(AdVersion $adVersion, string $newMainImageId): void
    {
        DB::beginTransaction();
        try {
            // Retirer le flag is_main de toutes les images
            $adVersion->images()->updateExistingPivot(
                $adVersion->images()->pluck('property_images.id'),
                ['is_main' => false]
            );

            // Activer is_main pour la nouvelle image
            $adVersion->images()->updateExistingPivot($newMainImageId, ['is_main' => true]);

            // Mettre à jour main_photo_filename
            $newMainImage = PropertyImage::findOrFail($newMainImageId);
            $adVersion->update([
                'main_photo_filename' => $newMainImage->filename
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Supprime une image d'une AdVersion (et physiquement si plus utilisée).
     *
     * @param AdVersion $adVersion
     * @param string $imageId
     */
    public function detachImage(AdVersion $adVersion, string $imageId): void
    {
        DB::beginTransaction();
        try {
            $image = PropertyImage::findOrFail($imageId);

            // Détacher de l'AdVersion
            $adVersion->images()->detach($imageId);

            // Si l'image n'est plus utilisée par aucune version, on la supprime
            if ($image->adVersions()->count() === 0) {
                $this->deleteImageFile($image);
                $image->delete();
            }

            // Mettre à jour photos_json
            $this->updatePhotosJson($adVersion);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Supprime physiquement le fichier image du storage.
     */
    private function deleteImageFile(PropertyImage $image): void
    {
        if (Storage::disk('public')->exists($image->filepath)) {
            Storage::disk('public')->delete($image->filepath);
        }
    }

    /**
     * Récupère toutes les images d'une AdVersion avec info is_main.
     */
    public function getImagesForAdVersion(AdVersion $adVersion): \Illuminate\Support\Collection
    {
        return $adVersion->images()
            ->withPivot('is_main')
            ->orderByPivot('is_main', 'desc') // Image principale en premier
            ->get();
    }

    /**
     * Récupère l'image principale d'une AdVersion.
     */
    public function getMainImage(AdVersion $adVersion): ?PropertyImage
    {
        return $adVersion->mainImage()->first();
    }

    /**
     * Nettoie les images orphelines (non liées à aucune AdVersion).
     * À lancer via une commande Artisan planifiée.
     */
    public function cleanOrphanImages(): int
    {
        $orphanImages = PropertyImage::doesntHave('adVersions')->get();
        $count = 0;

        foreach ($orphanImages as $image) {
            $this->deleteImageFile($image);
            $image->delete();
            $count++;
        }

        return $count;
    }
}