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


    // ------------------------------------------
    // A. Enregistrement (MIS À JOUR pour la clarté)
    // ------------------------------------------

   // ------------------------------------------

   public function createVersion(array $data, string $userId, ?string $adId = null): ?AdVersion
    {
        // 1. Initialiser la transaction pour garantir l'intégrité
        return DB::transaction(function () use ($data, $userId, $adId) {
                
                // Champs gérés par le système (non fournis par l'utilisateur)
                $data['id'] = Str::uuid();
                $data['ad_id'] = $adId; 
                
                // Le statut initial est 'draft' si l'utilisateur modifie, ou 'pending' si c'est une première création
                $initialStatus = $adId ? 'draft' : 'pending';
                $data['status'] = $initialStatus;
    
                // Si photos_json est une chaîne (comme c'est le cas avec un JSON), on la décode pour la création
                if (isset($data['photos_json']) && is_string($data['photos_json'])) {
                    $data['photos_json'] = json_decode($data['photos_json'], true);
                }
    
                // 2. Créer la nouvelle version de l'annonce
                $newVersion = $this->adVersion->create($data);
    
                // 3. Traitement de l'Annonce Mère (Ad)
                if (!$adId) {
                    // C'est une NOUVELLE ANNONCE
                    $ad = $this->ad->create([
                        'id' => Str::uuid(),
                        'user_id' => $userId, // L'ID de l'utilisateur qui crée l'annonce
                        'active_version_id' => $newVersion->id, // La première version devient la version active
                        'global_status' => 'draft', // Le statut global sera 'draft' avant modération
                        'published_at' => null, // Non publiée tant que non validée
                    ]);
                    
                    // Mettre à jour la version avec l'ID de l'annonce mère
                    $newVersion->ad_id = $ad->id;
                    $newVersion->save();
                    
                } else {
                    // C'est une MODIFICATION : on met à jour le statut global de l'annonce mère si nécessaire
                    $ad = $this->ad->findOrFail($adId);
                    
                    // Si l'annonce était active, elle passe en "draft" ou "inactive" le temps que la nouvelle version soit validée.
                    // Pour l'instant, on ne touche qu'à la version. La logique de publication sera dans un service.
                }
    
                return $newVersion;
            });
        // try {
            
        // } catch (Throwable $e) {
        //     // Log l'erreur pour le débogage et renvoie null en cas d'échec de la transaction
        //     Log::error("Erreur lors de la création ou mise à jour de la version d'annonce: " . $e->getMessage());
        //     return null;
        // }
    }

  
  
}