<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $plansData = [
            // ------------------ PLAN GRATUIT ------------------
            [
                'name' => 'Gratuit',
                'description' => 'Accès basique à la plateforme.',
                'price' => 0.00,
                'duration_days' => 365, // Par exemple, valide pour un an
                'max_rent_ads' => 3, // 2 (long) + 1 (court) = 3 annonces de location au total (Hypothèse d'agrégation)
                'max_sale_ads' => 1,
                'visibility_level' => 'normal',
                'has_dashboard' => true,
                'has_verified_badge' => false,
                'has_multi_user_management' => false,
                'has_priority_support' => false,
            ],
            // ------------------ PLAN STANDARD ------------------
            [
                'name' => 'Standard',
                'description' => 'Idéal pour les petits propriétaires et les utilisateurs fréquents.',
                'price' => 19.99, 
                'duration_days' => 30,
                'max_rent_ads' => 15, // 10 (long) + 5 (court) = 15 annonces de location au total
                'max_sale_ads' => 5,
                'visibility_level' => 'increased',
                'has_dashboard' => true,
                'has_verified_badge' => false,
                'has_multi_user_management' => false,
                'has_priority_support' => false,
            ],
            // ------------------ PLAN PRÉMIUM ------------------
            [
                'name' => 'Prémium',
                'description' => 'La solution professionnelle avec toutes les fonctionnalités débloquées.',
                'price' => 99.99, 
                'duration_days' => 30,
                'max_rent_ads' => 9999, // Illimité
                'max_sale_ads' => 9999, // Illimité
                'visibility_level' => 'maximum',
                'has_dashboard' => true,
                'has_verified_badge' => true,
                'has_multi_user_management' => true, // Ajout de la gestion multi-utilisateur
                'has_priority_support' => true,
            ],
        ];

        foreach ($plansData as $data) {
            // Utiliser firstOrCreate pour éviter de créer des doublons si le seeder est exécuté plusieurs fois
            Plan::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
