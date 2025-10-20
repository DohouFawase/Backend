<?php

namespace App\Http\Requests\PropertyType;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdatePropertyTypeFormRequest extends FormRequest
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
            //
             'name' => ['nullable', 'string', 'unique:property_types,name', 'min:1', 'max:100'], 
        ];
    }


      public function messages(): array
    {
        return [
          'name.nullable' => 'Le Type de Bien est obligatoire.',
            'name.unique' => 'Cet type de bien existe déjà. Veuillez choisir un autre nom.',
            'name.max' => 'Le nom de ce bien ne peut pas dépasser 100 caractères.',
            
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
