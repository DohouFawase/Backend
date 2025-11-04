<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Authentication\UserRepository;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private UserRepository $userRepository) {}

    // --- 1. LISTER LES UTILISATEURS ACTIFS ---
    public function index(): JsonResponse
    {
        try {
            $users = $this->userRepository->get(); // Récupère seulement les actifs
            
            if ($users->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun utilisateur actif trouvé',
                    'data' => $users,
                ], 404);
            }

            return response()->json([ // Utilisation de response()->json pour la cohérence
                'success' => true, 
                'message' => 'Liste des utilisateurs actifs récupérée avec succès.',
                'data' => $users, // Simplifié pour retourner directement la collection
            ], 200);
           
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur interne lors de la récupération des utilisateurs',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    // ----------------------------------------------------------------------
    // --- 2. LISTER LES UTILISATEURS DÉSACTIVÉS (ARCHIVÉS) ---

    public function showDeactivatedUsers(): JsonResponse
    {
        try {
            // Utilise la nouvelle méthode du dépôt
            $users = $this->userRepository->getOnlyDeactivated(); 

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun utilisateur archivé trouvé',
                    'data' => $users,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Liste des utilisateurs archivés récupérée avec succès.',
                'data' => $users,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur interne lors de la récupération des utilisateurs archivés',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    // ----------------------------------------------------------------------
    // --- 3. DÉSACTIVER (SOFT-DELETE) UN UTILISATEUR ---

    public function deactivateUser(string $id): JsonResponse
    {
        try {
            // Utilise la méthode deactivate du dépôt
            $deactivated = $this->userRepository->deactivate($id);

            if (!$deactivated) {
                // L'utilisateur n'a pas été trouvé (il pourrait déjà être désactivé)
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé ou déjà désactivé.',
                ], 404); 
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur désactivé (archivé) avec succès.',
            ], 200); // 200 OK pour une action de modification réussie

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur interne lors de la désactivation de l\'utilisateur.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    // ----------------------------------------------------------------------
    // --- 4. RÉACTIVER (RESTAURER) UN UTILISATEUR ARCHIVÉ ---

    public function reactivateUser(string $id): JsonResponse
    {
        try {
            // Utilise la méthode reactivate du dépôt
            $reactivated = $this->userRepository->reactivate($id);

            if (!$reactivated) {
                // L'utilisateur n'a pas été trouvé ou n'était pas désactivé
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé ou déjà actif.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur réactivé avec succès.',
            ], 200); // 200 OK

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur interne lors de la réactivation de l\'utilisateur.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }
}