<?php

namespace App\Http\Requests\PropertyImage;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AdVersion; // Assurez-vous que le modèle est importé

class CreatePropertyImageFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // L'autorisation est gérée ici ou via des middlewares
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     */
   public function rules(): array
    {
        return [
            'ad_version_id' => [
                'required', 
                'uuid', 
                // Assurez-vous d'importer Illuminate\Validation\Rule
                Rule::exists('ad_versions', 'id')
            ],
            
            // Les photos sont maintenant un tableau d'objets JSON
            'photos' => ['required', 'array', 'min:1', 'max:20'], 
            
            // Valide chaque élément du tableau 'photos'
            'photos.*.data' => ['required', 'string'], // Contient la chaîne Base64
            'photos.*.original_name' => ['nullable', 'string', 'max:255'], // Optionnel pour le nom

            // L'index principal est vérifié en tant qu'entier
            'main_image_index' => ['required', 'integer', 'min:0'], 
        ];
    }
    
    // ... prepareForValidation() est vide (comme précédemment) ...

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            
            // On récupère les données 'photos' directement de la requête JSON
            $photosData = $this->input('photos'); 
            $mainImageIndex = (int)$this->input('main_image_index');
            
            // On vérifie que la validation des photos n'a pas déjà échoué pour le type/format
            if (!$validator->errors()->has('photos') && $mainImageIndex !== null) {
                
                if (is_array($photosData)) {
                    $count = count($photosData);
                    
                    if ($mainImageIndex < 0 || $mainImageIndex >= $count) {
                        $validator->errors()->add(
                            'main_image_index', 
                            'L\'index de l\'image principale spécifié (' . $mainImageIndex . ') est en dehors de la plage des photos téléchargées (0 à ' . ($count - 1) . ').'
                        );
                    }
                }
            }
        });
    }

    /**
     * Personnalise les messages d'erreur de validation (Déjà fait en français).
     */
    public function messages(): array
    {
        return [
            'ad_version_id.required' => 'L\'identifiant de l\'annonce est obligatoire.',
            'ad_version_id.exists' => 'L\'annonce spécifiée n\'existe pas.',
            'photos.required' => 'Au moins une photo est requise.',
            'photos.array' => 'Le champ photos doit être un tableau.',
            'photos.max' => 'Vous ne pouvez télécharger qu\'un maximum de 20 photos.',
            'photos.*.image' => 'Le fichier :attribute doit être une image.',
            'photos.*.mimes' => 'Le fichier :attribute doit être de type :values (JPEG, PNG ou WebP).',
            'photos.*.max' => 'Le fichier :attribute est trop volumineux. La taille maximale autorisée est de 20 Mo.',
            'main_image_index.required' => 'L\'index de la photo principale est obligatoire.',
            'main_image_index.integer' => 'L\'index de la photo principale doit être un nombre entier.',
            'main_image_index.min' => 'L\'index de la photo principale ne peut pas être négatif.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422));
    }
}