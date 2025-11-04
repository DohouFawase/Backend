<?php

namespace App\Http\Repositories\Property;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlanRepository
{
    protected $plan;

    /**
     * Injection du modèle Plan.
     */
    public function __construct(Plan $plan)
    {
        $this->plan = $plan;
    }

    /**
     * Crée un nouveau Plan.
     *
     * @param array $data Les données validées (de CreatePlanFormRequest)
     * @return Plan|null
     */
    public function create(array $data): ?Plan
    {
        try {
            $plan = $this->plan->create($data);

            return $plan;
            // La méthode create d'Eloquent gère l'insertion et retourne l'objet créé.
        } catch (Throwable $e) {
            Log::error("Erreur création Plan: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un Plan par son ID.
     *
     * @param string $planId L'ID du plan
     * @return Plan|null
     */
    public function findById(string $planId): ?Plan
    {
        // On charge aussi la relation des abonnements pour avoir une vue complète
        return $this->plan->with(['subscriptions'])->find($planId);
    }

    /**
     * Récupère tous les Plans disponibles.
     *
     * @param int $perPage Nombre d'éléments par page (0 pour tout récupérer)
     * @return LengthAwarePaginator|Collection
     */
    public function getAll(int $perPage = 0)
    {
        $query = $this->plan->query();

        $query->orderBy('price', 'asc'); // Trier par prix croissant par défaut

        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }
    
    /**
     * Récupère les plans visibles publiquement.
     * (Utile pour le front-office, peut nécessiter un champ 'is_public' ou un filtre)
     *
     * @return Collection<Plan>
     */
    public function getPublicPlans(): Collection
    {
        // Supposons qu'un plan est public s'il a un prix > 0
        return $this->plan
            ->where('price', '>', 0)
            ->orderBy('price', 'asc')
            ->get();
    }


    /**
     * Met à jour un Plan existant.
     *
     * @param string $planId L'ID du plan à mettre à jour
     * @param array $data Les données validées
     * @return Plan|null
     */
    public function update(string $planId, array $data): ?Plan
    {
        $plan = $this->plan->findOrFail($planId);

        $plan->update($data);

        // Recharger les relations après la mise à jour si nécessaire
        $plan->load('subscriptions');

        return $plan;
       
    }

    /**
     * Supprime un Plan.
     *
     * @param string $planId L'ID du plan à supprimer
     * @return bool
     */
    public function delete(string $planId): bool
    {
        try {
            $plan = $this->plan->findOrFail($planId);

            // ⚠️ Sécurité: Avant de supprimer, il serait judicieux de vérifier si des
            // abonnements sont liés à ce plan et de gérer leur migration ou annulation.
            // Pour l'instant, on se contente de la suppression du Plan.
            if ($plan->subscriptions()->count() > 0) {
                 // Gérer la logique si des abonnements existent
                 // (ex: jeter une exception, archiver le plan au lieu de le supprimer)
                 // throw new \Exception("Impossible de supprimer : Plan lié à des abonnements existants.");
            }

            return $plan->delete();
        } catch (Throwable $e) {
            Log::error("Erreur suppression Plan (ID: {$planId}): " . $e->getMessage());
            return false;
        }
    }
}