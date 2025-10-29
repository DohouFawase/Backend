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
        
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10'
            ],

            // Chaque image doit respecter ces critères
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // Max 5 MB par image
                'dimensions:min_width=800,min_height=600,max_width=4000,max_height=4000'
            ]
        ];
    }


      public function messages(): array
    {
        return [
          'images.required' => 'Vous devez uploader au moins une image.',
            'images.min' => 'Vous devez uploader au moins une image.',
            'images.max' => 'Vous ne pouvez pas uploader plus de 10 images.',
            
            'images.*.image' => 'Le fichier doit être une image.',
            'images.*.mimes' => 'Les formats acceptés sont : JPEG, JPG, PNG, WEBP.',
            'images.*.max' => 'Chaque image ne doit pas dépasser 5 MB.',
            'images.*.dimensions' => 'Les images doivent avoir une taille entre 800x600 et 4000x4000 pixels.',
            
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


    //    protected function prepareForValidation(): void
    // {
    //     // Si main_image_index n'est pas fourni, on met 0 par défaut
    //     if (!$this->has('main_image_index')) {
    //         $this->merge([
    //             'main_image_index' => 0
    //         ]);
    //     }
    // }

    // /**
    //  * Récupère l'AdVersion concernée.
    //  */
    // public function getAdVersion()
    // {
    //     return \App\Models\AdVersion::findOrFail($this->ad_version_id);
    // }
}
