<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Authentication\AuthRepository;
use App\Http\Requests\Auth\CheckEmailRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginFormRequest;
use App\Http\Requests\Auth\RegisterFormRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordWithOtpRequest;
use App\Http\Requests\Auth\VerifyAccountRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthRepository $authRepository) {}

    // ------------------------------------------
    // A. Enregistrement (Register)
    // ------------------------------------------
    public function register(RegisterFormRequest $request): JsonResponse
    {
        try {
            $createUser = $this->authRepository->UserRegister($request);

            // Correction de l'orthographe :
            return api_response(true, 'Inscription réussie ! Un code de vérification (OTP) a été envoyé à votre adresse e-mail.', $createUser, 201); // 201 Created

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
                return api_response(false, 'Identifiants invalides.', null, 401); // 401 Unauthorized
            }

            if ($result === 'unverified') {
                return api_response(false, "Votre compte n'a pas été vérifié. Veuillez valider votre code OTP.", null, 403); // 403 Forbidden
            }

            // Succès : Le résultat contient l'utilisateur et le token
            return api_response(true, 'Connexion réussie.', $result);

        } catch (\Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la tentative de connexion.', $e->getMessage(), 500);
        }
    }

    // ------------------------------------------
    // C. Déconnexion (Logout) - RÉVOCATION DU TOKEN
    // ------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authRepository->logout($request);

            return api_response(true, 'Déconnexion réussie. Token révoqué.', null, 200);

        } catch (\Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la déconnexion.', $e->getMessage(), 500);
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
            return api_response(true, 'Si votre adresse e-mail existe, un lien de réinitialisation vous a été envoyé.', null, 200);

        } catch (\Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la demande de réinitialisation.', $e->getMessage(), 500);
        }
    }

    // ------------------------------------------
    // E. Réinitialisation du Mot de Passe (Reset Password)
    // ------------------------------------------
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $user = $this->authRepository->resetPassword($request);

            if (! $user) {
                return api_response(false, 'Le token de réinitialisation est invalide ou a expiré.', null, 400); // 400 Bad Request
            }

            return api_response(true, 'Votre mot de passe a été réinitialisé avec succès.', $user, 200);

        } catch (\Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de la réinitialisation du mot de passe.', $e->getMessage(), 500);
        }
    }

    public function verifyOtpAndReset(ResetPasswordWithOtpRequest $request): JsonResponse
    {
        try {
            $success = $this->authRepository->validateOtpAndResetPassword($request->validated());

            if (! $success) {
                // Un échec ici signifie que l'OTP est incorrect ou expiré
                return api_response(false, 'Code OTP invalide ou expiré. Veuillez vérifier votre code ou redemander un nouvel envoi.', null, 400); // 400 Bad Request
            }

            return api_response(true, 'Votre mot de passe a été réinitialisé avec succès.', null, 200);

        } catch (\Throwable $e) {
            // En cas d'erreur serveur inattendue
            return api_response(false, "Une erreur est survenue lors de la vérification de l'OTP et la réinitialisation.", $e->getMessage(), 500);
        }
    }

    public function verifyAccount(VerifyAccountRequest $request): JsonResponse
    {
        try {
            $success = $this->authRepository->verifyAccount($request->validated());

            if (! $success) {
                return api_response(false, 'Le code de vérification est invalide ou a expiré.', null, 400);
            }

            return api_response(true, 'Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.', null, 200);

        } catch (\Throwable $e) {
            // En cas d'erreur serveur inattendue
            return api_response(false, 'Une erreur est survenue lors de la vérification de votre compte.', $e->getMessage(), 500);
        }
    }




    public function resendOtp(ResendOtpRequest $request): JsonResponse
{
    try {
        // Nous passons seulement l'email validé
        $success = $this->authRepository->resendVerificationOtp($request->email);

        if (!$success) {
            // Cela peut arriver si l'utilisateur est déjà vérifié (sécurité)
            return api_response(false, "Votre compte est déjà vérifié ou une erreur est survenue.", null, 400); 
        }
        
        return api_response(true, "Un nouveau code de vérification a été envoyé à votre adresse e-mail.", null, 200);

    } catch (\Throwable $e) {
        // En cas d'erreur serveur inattendue
        return api_response(false, "Une erreur est survenue lors du renvoi du code.", $e->getMessage(), 500);
    }
}




public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
{
    try {
        $result = $this->authRepository->validateLoginOtpAndIssueToken($request->validated());

        if ($result === false) {
            return api_response(false, "Code OTP invalide ou expiré.", null, 403); // 403 Forbidden
        }
        
        // Succès : Le résultat contient l'utilisateur et le token
        return api_response(true, 'Connexion réussie. Bienvenue !', $result);

    } catch (\Throwable $e) {
        return api_response(false, 'Une erreur est survenue lors de la validation de l\'OTP.', $e->getMessage(), 500);
    }
}


public function checkEmail(CheckEmailRequest $request): JsonResponse
{
    try {
        $result = $this->authRepository->checkEmail($request->email);
        
        if ($result === false) {
             // Aucun compte trouvé avec cet email
            return api_response(false, 'Aucun compte associé à cet email.', null, 404); // 404 Not Found
        }
          if ($result['status'] === 'unverified') {
            // Le compte existe mais n'est pas vérifié
            return api_response(
                false, 
                "Votre compte n'a pas été vérifié. Veuillez valider votre code OTP d'inscription.", 
                ['verified' => false, 'email' => $result['user']->email], 
                403 // 403 Forbidden
            );
        }

        if ($result['status'] === 'otp_sent') {
            // Succès : OTP envoyé
            return api_response(
                true, 
                'Un code de vérification a été envoyé à votre adresse e-mail.', 
                ['otp_sent' => true, 'email' => $result['user']->email], 
                200
            );
        }

        // Succès : Le résultat contient l'utilisateur et le token
        return api_response(false, 'Une erreur inattendue est survenue.', null, 500);

    } catch (\Throwable $e) {
        return api_response(false, 'Une erreur est survenue lors de la validation de l\'OTP.', $e->getMessage(), 500);
    }
}


}
