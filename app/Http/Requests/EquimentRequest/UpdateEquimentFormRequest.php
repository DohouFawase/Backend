<?php

namespace App\Http\Requests\EquimentRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateEquimentFormRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    
     public function rules(): array
    {
        $equipmentId = $this->route('equipment') ? $this->route('equipment')->id : null;
        return [
        'name' => [
                'sometimes', // Utilisez 'sometimes' pour ne valider que si le champ est présent
                'required_with:name', // Assure que si 'name' est présent, il n'est pas vide
                'string', 
                'min:1', 
                'max:100',
                Rule::unique('equipments', 'name')->ignore($equipmentId, 'id'),
            ],
            
            'category_id' => [
                'sometimes', 
                'uuid', 
                'exists:equipment_categories,id'
            ],
        ];
    }


    public function messages(): array
    {
        return [
          'name.required_with' => 'Le nom de l\'équipement est obligatoire.',
            'name.unique' => 'Cet équipement existe déjà. Veuillez choisir un autre nom.',
            'name.max' => 'Le nom de l\'équipement ne peut pas dépasser 100 caractères.',
            
            'category_id.uuid' => 'L\'identifiant de la catégorie doit être un UUID valide.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.'
        ];
    }

      public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422));
    }
}