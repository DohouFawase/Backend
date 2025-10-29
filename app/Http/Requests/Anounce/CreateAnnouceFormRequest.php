<?php

namespace App\Http\Requests\Anounce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateAnnouceFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // --- Étape 1 : Type & SEO
            'ad_type' => ['required', Rule::in(['for_rent', 'for_sale'])],
            'seo_description' => ['required', 'string', 'max:255'],

            // --- Étape 2 : Localisation
            'full_address' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:100'],
            'additional_info' => ['nullable', 'string'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],

            // --- Étape 3 : Caractéristiques
            'area_value' => ['nullable', 'numeric'],
            'area_unit' => ['nullable', Rule::in(['sqm', 'ha'])],
            'unit_count' => ['nullable', 'integer', 'min:1'],
            'construction_type' => ['nullable', 'string', 'max:50'],
            'electricity_type' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'min:50'],
            'legal_status' => ['nullable', 'string', 'max:100'],
            'accessibility' => ['nullable', 'string', 'max:100'],
            'usage_type' => ['nullable', 'string', 'max:100'],

            // --- Étape 4 : Tarification
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            
            'commission' => [Rule::requiredIf($this->ad_type === 'for_rent'), 'nullable', 'numeric', 'min:0'],
            'deposit_months' => [Rule::requiredIf($this->ad_type === 'for_rent'), 'nullable', 'integer', 'min:0'],
            'periodicity' => [
                Rule::requiredIf($this->ad_type === 'for_rent'), 
                'nullable', 
                Rule::in(['day', 'night', 'week', 'month'])
            ],
            'is_negotiable' => [Rule::requiredIf($this->ad_type === 'for_sale'), 'boolean'], 
            
            'property_type_id' => ['required', 'uuid', 'exists:property_types,id'],
            
            // 🎯 Equipments : obligatoire
            'equipments' => ['required', 'array', 'min:1'],
            'equipments.*' => ['uuid', 'exists:equipments,id'],
            
            // --- Étape 5 : Médias
            // ✅ IMAGES OBLIGATOIRES : au moins 1 image requise
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['uuid', 'exists:property_images,id'],
            'main_image_id' => ['required', 'uuid', 'exists:property_images,id'],
            
            // Champs legacy
            'photos_json' => ['nullable', 'json'], 
            'main_photo_filename' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'max' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
            'min' => 'Le champ :attribute doit contenir au moins :min.',
            
            'ad_type.in' => 'Le type d\'annonce doit être "for_rent" (location) ou "for_sale" (vente).',
            'city.required' => 'La ville est obligatoire pour la publication.',
            'description.min' => 'La description doit faire au moins 50 caractères pour être complète.',
            
            'price.required' => 'Le prix est obligatoire.',
            'commission.required_if' => 'La commission est obligatoire pour les annonces de location.',
            'deposit_months.required_if' => 'Le nombre de mois de caution est obligatoire pour la location.',
            'periodicity.required_if' => 'La périodicité est obligatoire pour la location.',
            'is_negotiable.required_if' => 'La propriété négociable est obligatoire pour la vente.',
            
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            
            'video_url.url' => 'L\'URL de la vidéo n\'est pas valide.',
            'photos_json.json' => 'Le champ photos_json doit être un format JSON valide.',
            
            'equipments.required' => 'Au moins un équipement doit être sélectionné.',
            'equipments.*.exists' => 'Un des équipements sélectionnés n\'existe pas.',
            'images.*.exists' => 'Une des images sélectionnées n\'existe pas.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_negotiable' => filter_var($this->is_negotiable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}