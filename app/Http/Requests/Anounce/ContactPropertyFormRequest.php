<?php

namespace App\Http\Requests\Anounce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request pour la recherche de locations (for_rent)
 */
class ContactPropertyFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Contact public
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'visitor_name' => ['required', 'string', 'min:2', 'max:100'],
            'visitor_email' => ['required', 'email', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'visitor_name.required' => 'Votre nom est obligatoire.',
            'visitor_name.min' => 'Votre nom doit contenir au moins 2 caractères.',
            'visitor_email.required' => 'Votre email est obligatoire.',
            'visitor_email.email' => 'Veuillez fournir une adresse email valide.',
            'message.required' => 'Le message est obligatoire.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
            'message.max' => 'Votre message ne peut pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreur de validation du formulaire de contact.',
            'errors' => $validator->errors()
        ], 422));
    }
}
