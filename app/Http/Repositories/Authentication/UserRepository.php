<?php

namespace App\Http\Repositories\Authentication;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function __construct(private User $user) {}

    /**
     * Récupère tous les utilisateurs actifs (ceux qui n'ont pas de deleted_at).
     * @return Collection|array
     */
    public function get(): Collection|array 
    {
        // Par défaut, Eloquent ignore les utilisateurs "soft-deleted"
        return $this->user->get();
    }

    /**
     * Désactive (Soft Delete) un utilisateur par son ID.
     * Met à jour la colonne 'deleted_at'.
     * @param string $id
     * @return bool|null
     */
    public function deactivate(string $id): ?bool
    {
        // 1. Trouver l'utilisateur actif
        $user = $this->user->find($id);

        if (!$user) {
            return false;
        }

        // 2. Supprime logiquement l'utilisateur (remplit 'deleted_at')
        return $user->delete();
    }

    /**
     * Réactive (Restaure) un utilisateur archivé par son ID.
     * Remet la colonne 'deleted_at' à NULL.
     * @param string $id
     * @return bool
     */
    public function reactivate(string $id): bool
    {
        // 1. Trouver l'utilisateur, y compris ceux qui sont "soft-deleted" (via ->withTrashed())
        $user = $this->user->withTrashed()->find($id);

        if (!$user) {
            return false;
        }

        // 2. Restaure l'utilisateur (met 'deleted_at' à NULL)
        return $user->restore();
    }
    
    /**
     * Récupère tous les utilisateurs, y compris les désactivés (archivés).
     * @return Collection|array
     */
    public function getAllWithDeactivated(): Collection|array
    {
        // Utilise withTrashed() pour inclure les utilisateurs dont deleted_at n'est pas NULL
        return $this->user->withTrashed()->get();
    }
    
    /**
     * Récupère uniquement les utilisateurs désactivés (archivés).
     * @return Collection|array
     */
    public function getOnlyDeactivated(): Collection|array
    {
        // Utilise onlyTrashed() pour inclure SEULEMENT les utilisateurs dont deleted_at n'est pas NULL
        return $this->user->onlyTrashed()->get();
    }
}