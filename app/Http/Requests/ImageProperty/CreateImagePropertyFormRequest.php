<?php

namespace App\Http\Requests\ImageProperty;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateImagePropertyFormRequest extends FormRequest
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
                 // L'AdVersion doit exister
            'ad_version_id' => [
                'required',
                'uuid',
                'exists:ad_versions,id'
            ],

            'images' => [
                'required',
                'array',
                'min:1',
                'max:10'
            ],

            'images.*' => [
                'required',
                'image', 
                'mimes:jpeg,jpg,png,webp', 
                // 'max:5120', 
            ],

            'main_image_index' => [
                'nullable',
                'integer',
                'min:0',
                'max:9'
            ]
        ];
    }


      public function messages(): array
    {
        return [
           'ad_version_id.required' => 'L\'ID de la version d\'annonce est requis.',
            'ad_version_id.exists' => 'Cette version d\'annonce n\'existe pas.',
            
            'images.required' => 'Vous devez uploader au moins une image.',
            'images.min' => 'Vous devez uploader au moins une image.',
            'images.max' => 'Vous ne pouvez pas uploader plus de 10 images.',
            
            'images.*.image' => 'Le fichier doit être une image.',
            'images.*.mimes' => 'Les formats acceptés sont : JPEG, JPG, PNG, WEBP.',
            'images.*.max' => 'Chaque image ne doit pas dépasser 5 MB.',
            'images.*.dimensions' => 'Les images doivent avoir une taille entre 800x600 et 4000x4000 pixels.',
            
            'main_image_index.integer' => 'L\'index de l\'image principale doit être un nombre entier.',
            'main_image_index.min' => 'L\'index de l\'image principale doit être au minimum 0.',
            'main_image_index.max' => 'L\'index de l\'image principale ne peut pas dépasser 9.'
            
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


       protected function prepareForValidation(): void
    {
        // Si main_image_index n'est pas fourni, on met 0 par défaut
        if (!$this->has('main_image_index')) {
            $this->merge([
                'main_image_index' => 0
            ]);
        }
    }

    /**
     * Récupère l'AdVersion concernée.
     */
    public function getAdVersion()
    {
        return \App\Models\AdVersion::findOrFail($this->ad_version_id);
    }
}
