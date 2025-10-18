<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterFormRequest;
use App\Http\Requests\Auth\LoginFormRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Repositories\AuthRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private AuthRepository $authRepository) {
        
    }
    
    // ------------------------------------------
    // A. Enregistrement (Register)
    // ------------------------------------------
    public function register (RegisterFormRequest $request): JsonResponse
    {
        try {
            $createUser = $this->authRepository->UserRegister($request);
            
            // Correction de l'orthographe :
            return api_response(true, "Inscription réussie ! Un code de vérification (OTP) a été envoyé à votre adresse e-mail.", $createUser, 201); // 201 Created
            
        } catch (\Throwable $e) {
            // Correction de l'orthographe :
            return api_response(false, "Une erreur est survenue lors de l'inscription.", $e->getMessage(), 500); // 500 Internal Server Error
        }
    }

    // ------------------------------------------
    // B. Connexion (Login) - GAGNER UN TOKEN JWT
    // ------------------------------------------
    public function login(LoginFormRequest $request): JsonResponse
    {
        try {
            $result = $this->authRepository->login($request);
            
            if ($result === false) {
                return api_response(false, "Identifiants invalides.", null, 401); // 401 Unauthorized
            }

            if ($result === 'unverified') {
                 return api_response(false, "Votre compte n'a pas été vérifié. Veuillez valider votre code OTP.", null, 403); // 403 Forbidden
            }

            // Succès : Le résultat contient l'utilisateur et le token
            return api_response(true, "Connexion réussie.", $result);

        } catch (\Throwable $e) {
            return api_response(false, "Une erreur est survenue lors de la tentative de connexion.", $e->getMessage(), 500);
        }
    }

    // ------------------------------------------
    // C. Déconnexion (Logout) - RÉVOCATION DU TOKEN
    // ------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authRepository->logout($request);

            return api_response(true, "Déconnexion réussie. Token révoqué.", null, 200);

        } catch (\Throwable $e) {
            return api_response(false, "Une erreur est survenue lors de la déconnexion.", $e->getMessage(), 500);
        }
    }

    // ------------------------------------------
    // D. Mot de Passe Oublié (Forgot Password)
    // ------------------------------------------
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authRepository->forgotPassword($request);
            
            // Message générique pour des raisons de sécurité (ne pas confirmer l'existence de l'email)
            return api_response(true, "Si votre adresse e-mail existe, un lien de réinitialisation vous a été envoyé.", null, 200);

        } catch (\Throwable $e) {
            return api_response(false, "Une erreur est survenue lors de la demande de réinitialisation.", $e->getMessage(), 500);
        }
    }

    // ------------------------------------------
    // E. Réinitialisation du Mot de Passe (Reset Password)
    // ------------------------------------------
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $user = $this->authRepository->resetPassword($request);

            if (!$user) {
                return api_response(false, "Le token de réinitialisation est invalide ou a expiré.", null, 400); // 400 Bad Request
            }
            
            return api_response(true, "Votre mot de passe a été réinitialisé avec succès.", $user, 200);

        } catch (\Throwable $e) {
            return api_response(false, "Une erreur est survenue lors de la réinitialisation du mot de passe.", $e->getMessage(), 500);
        }
    }
}