<?php

namespace App\Http\Requests\Auth;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginFormRequest extends FormRequest
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
    {return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }


    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse e-mail est obligatoire pour la connexion.',
            'password.required' => 'Le mot de passe est obligatoire.',
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
    // Supprime les espaces de début et de fin de l'email et du mot de passe.
    $this->merge([
        'email' => trim($this->email),
        'password' => trim($this->password),
    ]);
}
}
