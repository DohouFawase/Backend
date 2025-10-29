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
    protected $imageRepository;

    public function __construct(
        AdVersion $adVersion,
        Ad $ad,
        PropertyImageRepository $imageRepository
    ) {
        $this->adVersion = $adVersion;
        $this->ad = $ad;
        $this->imageRepository = $imageRepository;
    }

    /**
     * Crée une nouvelle version d'annonce avec images.
     *
     * @param array $data Les données validées
     * @param string $userId L'ID de l'utilisateur
     * @param string|null $adId L'ID de l'annonce mère (null = nouvelle annonce)
     * @return AdVersion|null
     */
    public function createVersion(array $data, string $userId, ?string $adId = null): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($data, $userId, $adId) {

                // ========================================
                // 1. Extraction des données de relations
                // ========================================

                $equipmentIds = $data['equipments'] ?? [];

                // ✅ Images OBLIGATOIRES
                $imageIds = $data['images']; // Array d'UUIDs
                $mainImageId = $data['main_image_id'];

                // Nettoyage de $data
                unset(
                    $data['equipments'],
                    $data['images'],
                    $data['main_image_id'],
                    $data['photos_json'],
                    $data['main_photo_filename']
                );

                // ========================================
                // 2. Préparation des données de la version
                // ========================================

                $data['id'] = Str::uuid()->toString();
                $data['ad_id'] = $adId; // NULL pour nouvelle annonce
                $data['status'] = $adId ? 'draft' : 'pending';

                // ========================================
                // 3. Création de la version
                // ========================================

                $newVersion = $this->adVersion->create($data);

                // ========================================
                // 4. Gestion de l'annonce mère (Ad)
                // ========================================

                if (!$adId) {
                    // NOUVELLE ANNONCE
                    $ad = $this->ad->create([
                        'id' => Str::uuid()->toString(),
                        'user_id' => $userId,
                        'active_version_id' => $newVersion->id,
                        'global_status' => 'draft',
                        'published_at' => null,
                    ]);

                    // Lier la version à l'annonce
                    $newVersion->ad_id = $ad->id;
                    $newVersion->save();
                } else {
                    // MODIFICATION
                    $ad = $this->ad->findOrFail($adId);
                }

                // ========================================
                // 5. Association des équipements
                // ========================================

                if (!empty($equipmentIds)) {
                    $newVersion->equipments()->sync($equipmentIds);
                }

                // ========================================
                // 6. Association des images
                // ========================================

                $this->imageRepository->attachImagesToVersion(
                    $newVersion,
                    $imageIds,
                    $mainImageId
                );

                // Charger les relations pour la réponse
                $newVersion->load(['images', 'equipments', 'propertyType']);

                return $newVersion;
            });
        } catch (Throwable $e) {
            Log::error("Erreur création AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Récupère une version spécifique avec toutes ses relations.
     *
     * @param string $versionId L'ID de la version
     * @return AdVersion|null
     */
    public function findById(string $versionId): ?AdVersion
    {
        return $this->adVersion
            ->with([
                'ad',
                'propertyType',
                'equipments',
                'images' => function ($query) {
                    $query->orderBy('pivot_is_main', 'desc')
                        ->orderBy('pivot_created_at', 'asc');
                }
            ])
            ->find($versionId);
    }

    /**
     * Récupère une version spécifique (avec exception si non trouvée).
     *
     * @param string $versionId L'ID de la version
     * @return AdVersion
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdOrFail(string $versionId): AdVersion
    {
        return $this->adVersion
            ->with([
                'ad',
                'propertyType',
                'equipments',
                'images' => function ($query) {
                    $query->orderBy('pivot_is_main', 'desc')
                        ->orderBy('pivot_created_at', 'asc');
                }
            ])
            ->findOrFail($versionId);
    }

    /**
     * Met à jour une version existante (seulement si status = draft).
     *
     * @param string $versionId L'ID de la version
     * @param array $data Les données à mettre à jour
     * @return AdVersion|null
     */
    public function update(string $versionId, array $data): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($versionId, $data) {

                $version = $this->adVersion->findOrFail($versionId);

                // ⚠️ Sécurité : seulement les drafts peuvent être modifiés
                if ($version->status !== 'draft') {
                    throw new \Exception("Seules les versions 'draft' peuvent être modifiées");
                }

                // Extraction des relations
                $equipmentIds = $data['equipments'] ?? null;
                $imageIds = $data['images'] ?? null;
                $mainImageId = $data['main_image_id'] ?? null;

                unset(
                    $data['equipments'],
                    $data['images'],
                    $data['main_image_id'],
                    $data['photos_json'],
                    $data['main_photo_filename']
                );

                // Mise à jour des champs principaux
                $version->update($data);

                // Mise à jour des équipements si fournis
                if ($equipmentIds !== null) {
                    $version->equipments()->sync($equipmentIds);
                }

                // Mise à jour des images si fournies
                if ($imageIds !== null && $mainImageId !== null) {
                    $this->imageRepository->attachImagesToVersion(
                        $version,
                        $imageIds,
                        $mainImageId
                    );
                }

                $version->load(['images', 'equipments', 'propertyType']);

                return $version;
            });
        } catch (Throwable $e) {
            Log::error("Erreur mise à jour AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Supprime une version (avec vérifications).
     *
     * @param string $versionId L'ID de la version
     * @return bool
     */
    public function delete(string $versionId): bool
    {
        try {
            return DB::transaction(function () use ($versionId) {

                $version = $this->adVersion->findOrFail($versionId);

                // ⚠️ Vérification : ne pas supprimer la version active
                if ($version->ad && $version->ad->active_version_id === $versionId) {
                    throw new \Exception("Impossible de supprimer la version active");
                }

                // ⚠️ Vérification : si c'est la seule version
                $versionCount = $this->adVersion
                    ->where('ad_id', $version->ad_id)
                    ->count();

                if ($versionCount === 1) {
                    // Supprimer aussi l'annonce parent
                    $version->ad->delete();
                }

                // Détacher les relations (équipements, images)
                $version->equipments()->detach();
                $version->images()->detach();

                // Supprimer la version
                return $version->delete();
            });
        } catch (Throwable $e) {
            Log::error("Erreur suppression AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Récupère toutes les versions avec pagination.
     */
    public function getAll(int $perPage = 15, array $filters = [])
    {
        $query = $this->adVersion->query()
            ->with(['ad', 'propertyType', 'equipments', 'images']);

        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par annonce mère
        if (isset($filters['ad_id'])) {
            $query->where('ad_id', $filters['ad_id']);
        }

        // Filtre par type de propriété
        if (isset($filters['property_type_id'])) {
            $query->where('property_type_id', $filters['property_type_id']);
        }

        // Filtre par type d'annonce (à louer / à vendre)
        if (isset($filters['ad_type'])) {
            $query->where('ad_type', $filters['ad_type']);
        }

        // Filtre par ville
        if (isset($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        // Filtre par quartier
        if (isset($filters['neighborhood'])) {
            $query->where('neighborhood', 'like', '%' . $filters['neighborhood'] . '%');
        }

        // Filtre par prix minimum
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        // Filtre par prix maximum
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Filtre par nombre de chambres minimum
        if (isset($filters['min_bedrooms'])) {
            $query->where('bedrooms', '>=', $filters['min_bedrooms']);
        }

        // Filtre par surface minimum
        if (isset($filters['min_area'])) {
            $query->where('area', '>=', $filters['min_area']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Recherche avancée d'annonces avec filtres étendus.
     */
    public function search(int $perPage = 15, array $filters = [])
    {
        $query = $this->adVersion->query()
            ->with(['ad', 'propertyType', 'equipments', 'images']);

        // Type d'annonce (OBLIGATOIRE pour search)
        if (isset($filters['ad_type'])) {
            $query->where('ad_type', $filters['ad_type']);
        }

        // Statut (par défaut : validated)
        $query->where('status', $filters['status'] ?? 'validated');

        // Type de propriété
        if (isset($filters['property_type_id'])) {
            $query->where('property_type_id', $filters['property_type_id']);
        }

        // Localisation
        if (isset($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (isset($filters['neighborhood'])) {
            $query->where('neighborhood', 'like', '%' . $filters['neighborhood'] . '%');
        }

        if (isset($filters['district'])) {
            $query->where('district', 'like', '%' . $filters['district'] . '%');
        }

        // Prix
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Chambres
        if (isset($filters['min_bedrooms'])) {
            $query->where('bedrooms', '>=', $filters['min_bedrooms']);
        }

        if (isset($filters['max_bedrooms'])) {
            $query->where('bedrooms', '<=', $filters['max_bedrooms']);
        }

        // Surface
        if (isset($filters['min_area'])) {
            $query->where('area_value', '>=', $filters['min_area']);
        }

        if (isset($filters['max_area'])) {
            $query->where('area_value', '<=', $filters['max_area']);
        }

        // Spécifique LOCATION
        if (isset($filters['periodicity'])) {
            $query->where('periodicity', $filters['periodicity']);
        }

        // Spécifique VENTE
        if (isset($filters['is_negotiable'])) {
            $query->where('is_negotiable', $filters['is_negotiable']);
        }

        if (isset($filters['legal_status'])) {
            $query->where('legal_status', $filters['legal_status']);
        }

        // Filtrage par équipements
        if (isset($filters['equipment_ids']) && is_array($filters['equipment_ids'])) {
            $query->whereHas('equipments', function ($q) use ($filters) {
                $q->whereIn('equipments.id', $filters['equipment_ids']);
            });
        }

        // Tri par prix croissant par défaut (pertinent pour recherche)
        $query->orderBy('price', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Récupère toutes les versions d'une annonce.
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
     * Récupère les versions avec compteurs.
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
     * Récupère les versions actives.
     */
    public function getAllActive(int $perPage = 15)
    {
        return $this->adVersion->query()
            ->where('status', 'validated')
            ->with(['ad', 'propertyType', 'equipments', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }


    /**
     * Valide une version d'annonce (ADMIN).
     * Change le statut à 'validated' et enregistre qui a validé.
     *
     * @param string $versionId L'ID de la version
     * @param string $adminId L'ID de l'admin qui valide
     * @return AdVersion|null
     */
    public function validateVersion(string $versionId, string $adminId): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($versionId, $adminId) {

                $version = $this->adVersion->findOrFail($versionId);

                // ⚠️ Sécurité : seules les versions 'pending' peuvent être validées
                if ($version->status !== 'pending') {
                    throw new \Exception("Seules les versions 'pending' peuvent être validées");
                }

                // Mise à jour du statut
                $version->update([
                    'status' => 'validated',
                    'validated_at' => now(),
                    'validated_by_id' => $adminId
                ]);

                // Charger les relations pour la réponse
                $version->load(['ad', 'images', 'equipments', 'propertyType']);

                return $version;
            });
        } catch (Throwable $e) {
            Log::error("Erreur validation AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Rejette une version d'annonce (ADMIN).
     * Change le statut à 'refused'.
     *
     * @param string $versionId L'ID de la version
     * @param string|null $reason Raison du rejet (optionnel)
     * @return AdVersion|null
     */
    public function rejectVersion(string $versionId, ?string $reason = null): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($versionId, $reason) {

                $version = $this->adVersion->findOrFail($versionId);

                // ⚠️ Sécurité : seules les versions 'pending' peuvent être rejetées
                if ($version->status !== 'pending') {
                    throw new \Exception("Seules les versions 'pending' peuvent être rejetées");
                }

                // Mise à jour du statut
                $updateData = ['status' => 'refused'];

                // Si vous avez un champ rejection_reason dans votre table
                if ($reason) {
                    $updateData['rejection_reason'] = $reason;
                }

                $version->update($updateData);

                // Charger les relations pour la réponse
                $version->load(['ad', 'images', 'equipments', 'propertyType']);

                return $version;
            });
        } catch (Throwable $e) {
            Log::error("Erreur rejet AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }


    /**
     * Récupère toutes les versions en attente de validation.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllPending(int $perPage = 15)
    {
        return $this->adVersion->query()
            ->where('status', 'pending')
            ->with(['ad.user', 'propertyType', 'equipments', 'images'])
            ->orderBy('created_at', 'asc') // Les plus anciennes en premier
            ->paginate($perPage);
    }



    /**
     * Récupère toutes les versions validées.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllValidated(int $perPage = 15)
    {
        return $this->adVersion->query()
            ->where('status', 'validated')
            ->with(['ad.user', 'propertyType', 'equipments', 'images', 'validator'])
            ->orderBy('validated_at', 'desc')
            ->paginate($perPage);
    }


    /**
     * Récupère toutes les versions refusées.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllRefused(int $perPage = 15)
    {
        return $this->adVersion->query()
            ->where('status', 'refused')
            ->with(['ad.user', 'propertyType', 'equipments', 'images'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }



    /**
     * Active une version validée (la rend publique).
     * Met à jour l'annonce parent pour pointer vers cette version.
     *
     * @param string $versionId L'ID de la version
     * @return AdVersion|null
     */
    public function activateVersion(string $versionId): ?AdVersion
    {
        try {
            return DB::transaction(function () use ($versionId) {

                $version = $this->adVersion->findOrFail($versionId);

                // ⚠️ Sécurité : seules les versions 'validated' peuvent être activées
                if ($version->status !== 'validated') {
                    throw new \Exception("Seules les versions 'validated' peuvent être activées");
                }

                // Récupérer l'annonce parent
                $ad = $this->ad->findOrFail($version->ad_id);

                // Mettre à jour l'annonce pour pointer vers cette version
                $ad->update([
                    'active_version_id' => $versionId,
                    'global_status' => 'published',
                    'published_at' => now()
                ]);

                // Charger les relations pour la réponse
                $version->load(['ad', 'images', 'equipments', 'propertyType']);

                return $version;
            });
        } catch (Throwable $e) {
            Log::error("Erreur activation AdVersion: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }


    /**
     * Statistiques globales pour l'admin.
     *
     * @return array
     */
    public function getAdminStats(): array
    {
        return [
            'total' => $this->adVersion->count(),
            'pending' => $this->adVersion->where('status', 'pending')->count(),
            'validated' => $this->adVersion->where('status', 'validated')->count(),
            'refused' => $this->adVersion->where('status', 'refused')->count(),
            'draft' => $this->adVersion->where('status', 'draft')->count(),
            'archived' => $this->adVersion->where('status', 'archived')->count(),
        ];
    }
}
