<?php

namespace App\Http\Requests\Anounce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request pour la recherche de locations (for_rent)
 */
class SearchPropertyFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Recherche publique
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Type de propriété
            'property_type_id' => ['nullable', 'uuid', 'exists:property_types,id'],
            
            // Localisation
            'city' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            
            // Prix de vente
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            
            // Caractéristiques
            'min_bedrooms' => ['nullable', 'integer', 'min:0'],
            'max_bedrooms' => ['nullable', 'integer', 'min:0', 'gte:min_bedrooms'],
            'min_area' => ['nullable', 'numeric', 'min:0'],
            'max_area' => ['nullable', 'numeric', 'min:0', 'gte:min_area'],
            
            // Spécifique vente
            'is_negotiable' => ['nullable', 'boolean'],
            'legal_status' => ['nullable', 'string', 'max:100'],
            'construction_type' => ['nullable', 'string', 'max:50'],
            
            // Équipements
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['uuid', 'exists:equipments,id'],
            
            // Pagination
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            
            // Tri
            'sort_by' => ['nullable', 'in:price,created_at,area_value'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'property_type_id.exists' => 'Le type de propriété sélectionné n\'existe pas.',
            'max_price.gte' => 'Le prix maximum doit être supérieur ou égal au prix minimum.',
            'max_bedrooms.gte' => 'Le nombre maximum de chambres doit être supérieur ou égal au minimum.',
            'max_area.gte' => 'La surface maximale doit être supérieure ou égale à la surface minimale.',
            'equipment_ids.*.exists' => 'Un des équipements sélectionnés n\'existe pas.',
            'per_page.max' => 'Maximum 100 résultats par page.',
            'sort_by.in' => 'Le tri doit être par : price, created_at ou area_value.',
            'sort_order.in' => 'L\'ordre doit être : asc ou desc.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreur de validation des critères de recherche.',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Get validated data with defaults.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        return array_merge([
            'per_page' => 15,
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
        ], $validated);
    }
}
