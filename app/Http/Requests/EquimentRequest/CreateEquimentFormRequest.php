<?php

namespace App\Http\Requests\EquimentRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateEquimentFormRequest extends FormRequest
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
        return [
        'name' => ['required', 'string', 'unique:equipments,name', 'min:1', 'max:100'], 
            
            'category_id' => [
                'required', 
                'uuid', 
                'exists:equipment_categories,id'
            ],
        ];
    }


    public function messages(): array
    {
        return [
          'name.required' => 'Le nom de l\'équipement est obligatoire.',
            'name.unique' => 'Cet équipement existe déjà. Veuillez choisir un autre nom.',
            'name.max' => 'Le nom de l\'équipement ne peut pas dépasser 100 caractères.',
            
            'category_id.required' => 'L\'identifiant de la catégorie est obligatoire.',
            'category_id.uuid' => 'L\'identifiant de la catégorie doit être un UUID valide.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
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