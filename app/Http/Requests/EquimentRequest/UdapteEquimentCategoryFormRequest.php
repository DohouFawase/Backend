<?php

namespace App\Http\Requests\EquimentRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UdapteEquimentCategoryFormRequest extends FormRequest
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
            // Correction de 'nullabe' en 'nullable' pour icon_class
            'name' => ['nullable', 'string', 'min:1'],
            'icon_class' => ['nullable', 'string'],
        ];
    }


    public function messages(): array
    {
        return [
          'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères valide.',
            'name.unique' => 'Ce nom de catégorie existe déjà. Veuillez en choisir un autre.',
            'name.min' => 'Le nom de la catégorie doit contenir au moins 1 caractère.',
            'icon_class.string' => 'La classe d\'icône doit être une chaîne de caractères.',
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