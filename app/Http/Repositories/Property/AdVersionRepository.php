<?php

namespace App\Http\Repositories\Property;

use App\Models\Ad;
use App\Models\AdVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;


class AdVersionRepository
{
    protected $adVersion;
    protected $ad;

    public function __construct(AdVersion $adVersion, Ad $ad)
    {
        $this->adVersion = $adVersion;
        $this->ad = $ad;
    }

    /**
     * Crée une nouvelle version d'annonce et gère l'enregistrement des relations M:M.
     *
     * @param array $data Les données validées de la requête (incluant equipments, images, property_type_id).
     * @param string $userId L'ID de l'utilisateur.
     * @param string|null $adId L'ID de l'annonce mère si c'est une modification.
     * @return AdVersion|null La nouvelle version créée.
     */
    public function createVersion(array $data, string $userId, ?string $adId = null): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($data, $userId, $adId) {
                
                // ----------------------------------------------------
                // 1. Extraction et Nettoyage des Données de Relation
                // ----------------------------------------------------
                
                // Relations Many-to-Many (M:M)
                $equipmentIds = $data['equipments'] ?? [];
                $imageIds = $data['images'] ?? []; 
                $mainImageId = $data['main_image_id'] ?? null;
                
                // Nettoyage de $data pour l'insertion de la version (ne doit contenir que les colonnes directes)
                unset($data['equipments'], $data['images'], $data['main_image_id']); 
                
                // Suppression des anciens champs de photo/JSON (si la migration a été faite)
                unset($data['photos_json'], $data['main_photo_filename']); 

                // ----------------------------------------------------
                // 2. Préparation des Données de la Version
                // ----------------------------------------------------
                
                $data['id'] = Str::uuid();
                $data['ad_id'] = $adId; // Sera NULL lors de la première création
                $data['status'] = $adId ? 'draft' : 'pending'; // Détermine le statut initial

                // ----------------------------------------------------
                // 3. Création de la Version
                // ----------------------------------------------------
                $newVersion = $this->adVersion->create($data); // Nécessite 'property_type_id' dans $data

                // ----------------------------------------------------
                // 4. Traitement de l'Annonce Mère (Ad)
                // ----------------------------------------------------
                if (!$adId) {
                    // C'est une NOUVELLE ANNONCE
                    $ad = $this->ad->create([
                        'id' => Str::uuid(),
                        'user_id' => $userId, 
                        'active_version_id' => $newVersion->id, 
                        'global_status' => 'draft',
                        'published_at' => null,
                    ]);
                    
                    // Lier la version nouvellement créée à l'annonce mère
                    $newVersion->ad_id = $ad->id;
                    $newVersion->save();
                    
                } else {
                    // C'est une MODIFICATION
                    $ad = $this->ad->findOrFail($adId);
                }

                // ----------------------------------------------------
                // 5. Enregistrement des Relations Many-to-Many
                // ----------------------------------------------------
                
                // A. Équipements
                if (!empty($equipmentIds)) {
                    $newVersion->equipments()->sync($equipmentIds);
                }

                // B. Images (doit préparer les données du pivot pour 'is_main')
                if (!empty($imageIds)) {
                    $attachments = [];
                    foreach ($imageIds as $id) {
                        // Définit 'is_main' sur TRUE pour l'image dont l'ID correspond à mainImageId
                        $attachments[$id] = ['is_main' => ($id === $mainImageId)];
                    }
                    $newVersion->images()->sync($attachments);
                }

                return $newVersion;
            });
        } catch (Throwable $e) {
            // Log l'erreur et renvoie null en cas d'échec de la transaction
            Log::error("Erreur critique lors de la création ou mise à jour de la version d'annonce: " . $e->getMessage());
            return null;
        }
    }


/**
 * Récupère toutes les versions d'annonces avec pagination.
 *
 * @param int $perPage Nombre d'éléments par page (défaut: 15).
 * @param array $filters Filtres optionnels (status, ad_id, etc.).
 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
 */
public function getAll(int $perPage = 15, array $filters = [])
{
    $query = $this->adVersion->query()
        ->with(['ad', 'propertyType', 'equipments', 'images']);

    // Filtres optionnels
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (isset($filters['ad_id'])) {
        $query->where('ad_id', $filters['ad_id']);
    }

    if (isset($filters['property_type_id'])) {
        $query->where('property_type_id', $filters['property_type_id']);
    }

    // Tri par défaut (plus récentes en premier)
    $query->orderBy('created_at', 'desc');

    return $query->paginate($perPage);
}

/**
 * Récupère toutes les versions d'une annonce spécifique.
 *
 * @param string $adId L'ID de l'annonce.
 * @return \Illuminate\Database\Eloquent\Collection
 */
public function getAllByAd(string $adId)
{
    return $this->adVersion
        ->where('ad_id', $adId)
        ->with(['propertyType', 'equipments', 'images'])
        ->orderBy('created_at', 'desc')
        ->get();
}

/**
 * Récupère toutes les versions avec leur nombre d'équipements et d'images.
 *
 * @param int $perPage Nombre d'éléments par page.
 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
 */
public function getAllWithCounts(int $perPage = 15)
{
    return $this->adVersion->query()
        ->with(['ad', 'propertyType'])
        ->withCount(['equipments', 'images'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}

/**
 * Récupère toutes les versions actives (publiées).
 *
 * @param int $perPage Nombre d'éléments par page.
 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
 */
public function getAllActive(int $perPage = 15)
{
    return $this->adVersion->query()
        ->where('status', 'published')
        ->with(['ad', 'propertyType', 'equipments', 'images'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}
    
  
}