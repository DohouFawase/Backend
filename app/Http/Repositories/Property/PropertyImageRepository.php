<?php

namespace App\Http\Repositories\Property;



use App\Models\AdVersion;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// 💡 NOTE: Assurez-vous que votre fichier helper.php est chargé.
// En Laravel, ceci est souvent fait dans composer.json ou AppServiceProvider.php.
// Si ce n'est pas le cas, vous obtiendrez une erreur 'Call to undefined function uploadFile()'.

class PropertyImageRepository
{
    protected string $uploadPath = 'ad_version_images'; 

    /**
     * Sauvegarde les images et les attache à une version d'annonce spécifique.
     *
     * @param AdVersion $adVersion L'entité AdVersion existante.
     * @param array $files Tableau d'objets UploadedFile.
     * @param int $mainImageIndex Index de l'image principale.
     * @return array Résultat de l'opération.
     * @throws \Exception
     */
   
    public function saveImages(AdVersion $adVersion, ?array $files, int $mainImageIndex): array
    {
        // Robuste contre les cas où la validation échoue mais le code est atteint
        if (empty($files) || !is_array($files)) {
            return ['success' => false, 'message' => 'Aucune image valide à traiter.'];
        }
        
        DB::beginTransaction();

        try {
            $uploadedImagesIds = [];
            
            /** @var UploadedFile $file */
            foreach ($files as $index => $file) {
                
                // Sécurité : Ignorer les éléments qui ne sont pas des fichiers UploadedFile
                if (!isset($fileItem['data'])) { 
        continue; 
    }

                // 1. UPLOAD DU FICHIER EN UTILISANT LA FONCTION HELPER
                // (Nécessite que la fonction globale `uploadFile` soit disponible)
                $fileUrl = uploadFile($file, $this->uploadPath); 

                // 2. CRÉATION DE L'ENTITÉ PROPERTYIMAGE
                $propertyImage = PropertyImage::create([
                    'filename' => $file->getClientOriginalName(),
                    'filepath' => $fileUrl, 
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                // 3. ATTACHEMENT AVEC LES DONNÉES PIVOT
                $isMain = ($index === $mainImageIndex);

                $adVersion->images()->attach($propertyImage->id, [
                    'is_main' => $isMain,
                ]);

                $uploadedImagesIds[] = $propertyImage->id;
            }

            DB::commit();

            return [
                'success' => true, 
                'message' => 'Images sauvegardées et attachées à l\'annonce ID ' . $adVersion->id . '.',
                'ids' => $uploadedImagesIds
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la sauvegarde des images : " . $e->getMessage());
            throw new \Exception("Échec de la sauvegarde des images: " . $e->getMessage());
        }
    }
}



