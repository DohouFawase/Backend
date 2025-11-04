<?php

namespace App\Http\Requests\Planproperty;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePlanFormRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        // En supposant que vous voulez autoriser la requête si l'utilisateur est authentifié et a les permissions
        // Pour l'instant, on laisse à 'true' pour la fonctionnalité, mais vous devriez l'implémenter.
        return true;
    }

    /**
     * Obtient les règles de validation qui s'appliquent à la requête.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         $planId = $this->route('planID');
        return [
            // Informations de base
            'name' => [
                'nullable',
                'string',
                'max:255',
                  Rule::unique('plans', 'name')->ignore($planId)
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0', // Le prix ne peut pas être négatif
                'regex:/^\d+(\.\d{1,2})?$/' // Optionnel: format décimal (ex: 10 ou 10.99)
            ],
            'duration_days' => [
                'nullable',
                'integer',
                'min:1' // La durée doit être d'au moins 1 jour
            ],
            'visibility_level' => [
                'nullable',
                'string',
                'max:50' // À ajuster selon vos niveaux de visibilité (ex: 'standard', 'premium')
            ],

            // Limites d'annonces
            'max_rent_ads' => [
                'nullable',
                'integer',
                'min:-1' // -1 pour illimité, 0 ou plus pour une limite
            ],
            'max_sale_ads' => [
                'nullable',
                'integer',
                'min:-1'
            ],
            
            // Indicateurs de fonctionnalités (booléens)
            'has_dashboard' => [
                'nullable',
                'boolean'
            ],
            'has_verified_badge' => [
                'nullable',
                'boolean'
            ],
            'has_multi_user_management' => [
                'nullable',
                'boolean'
            ],
            'has_priority_support' => [
                'nullable',
                'boolean'
            ],
        ];
    }

    /**
     * Obtient les messages d'erreur personnalisés pour les règles de validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Nom
            'name.nullable' => 'Le nom du plan est obligatoire.',
            'name.unique' => 'Ce nom de plan existe déjà.',
            'name.max' => 'Le nom du plan ne doit pas dépasser 255 caractères.',
            
            // Description
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',

            // Prix
            'price.nullable' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            
            // Durée
            'duration_days.nullable' => 'La durée en jours est obligatoire.',
            'duration_days.integer' => 'La durée doit être un nombre entier.',
            'duration_days.min' => 'La durée minimale est de 1 jour.',
            
            // Niveau de visibilité
            'visibility_level.nullable' => 'Le niveau de visibilité est obligatoire.',
            
            // Limites d'annonces
            'max_rent_ads.nullable' => 'La limite d\'annonces de location est obligatoire.',
            'max_rent_ads.integer' => 'La limite d\'annonces de location doit être un nombre entier.',
            'max_rent_ads.min' => 'La limite d\'annonces de location doit être -1 (illimité) ou positive.',
            'max_sale_ads.nullable' => 'La limite d\'annonces de vente est obligatoire.',
            'max_sale_ads.integer' => 'La limite d\'annonces de vente doit être un nombre entier.',
            'max_sale_ads.min' => 'La limite d\'annonces de vente doit être -1 (illimité) ou positive.',
            
            // Indicateurs booléens
            'has_dashboard.nullable' => 'Le statut du tableau de bord est obligatoire.',
            'has_dashboard.boolean' => 'Le statut du tableau de bord doit être vrai ou faux.',
            // Répéter pour les autres has_*
            'has_verified_badge.nullable' => 'Le statut du badge vérifié est obligatoire.',
            'has_verified_badge.boolean' => 'Le statut du badge vérifié doit être vrai ou faux.',
            'has_multi_user_management.nullable' => 'Le statut de la gestion multi-utilisateur est obligatoire.',
            'has_multi_user_management.boolean' => 'Le statut de la gestion multi-utilisateur doit être vrai ou faux.',
            'has_priority_support.nullable' => 'Le statut du support prioritaire est obligatoire.',
            'has_priority_support.boolean' => 'Le statut du support prioritaire doit être vrai ou faux.',
        ];
    }

    /**
     * Gère un échec de validation.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreur de validation des données du plan.', // Message général
            'errors' => $validator->errors()
        ], 422));
    }
}