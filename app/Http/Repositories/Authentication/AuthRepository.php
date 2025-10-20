<?php

namespace App\Http\Repositories\Authentication;


use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use App\Mail\sendOtpMail;
use Carbon\Carbon; 

class AuthRepository
{
    public function __construct(private User $auth) {}

    // ------------------------------------------
    // A. Enregistrement (MIS À JOUR pour la clarté)
    // ------------------------------------------
    public function UserRegister($request)
    {
        $createRandomOtpNumber = random_int(100000, 999999);
        $hashedPassword = Hash::make($request->password);
        
        // Utilisez Carbon ou l'objet natif de Laravel
        $otpExpirationTime = Carbon::now()->addMinutes(10); 

        $cretateUser = $this->auth->create([
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $hashedPassword,
            'otp_code' => $createRandomOtpNumber,
            'otp_expires_at' => $otpExpirationTime,
            'is_verified' => false, 
        ]);

        // Correction : Utilisez une classe Notification pour la méthode notify()
        Mail::to($cretateUser->email)->send(new sendOtpMail($createRandomOtpNumber));

        return $cretateUser;
    }

  
    public function login($request)
    {
        $user = $this->auth->where('email', $request->email)->first();

        // 1. Vérification des identifiants et si l'utilisateur existe
        if (!$user || !Hash::check($request->password, $user->password)) {
            return false;
        }

        // 2. Vérification de l'état de vérification
        if (isset($user->is_verified) && !$user->is_verified) {
             return 'unverified';
        }
        
        // 3. Génération du Token JWT (simulé avec Sanctum)
        // Note : Si vous utilisez JWT-Auth, vous auriez `auth()->attempt()`
        // Avec Sanctum, on crée le token manuellement.
       $token = $user->createToken('authToken')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    
   
    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete(); 

        return true;
    }


   
    public function forgotPassword($request)
{
    // 1. Trouver l'utilisateur
    $user = $this->auth->where('email', $request->email)->first();

    // Bonne pratique : ne rien révéler si l'utilisateur n'existe pas.
    if (!$user) {
        return true; 
    }
    

    // Vérifie si l'utilisateur est actuellement bloqué
    if ($user->block_expires_at && Carbon::now()->lessThan($user->block_expires_at)) {
        // L'utilisateur est toujours bloqué, renvoie succès pour ne rien révéler.
        return true;
    }

    // L'utilisateur a le droit à une nouvelle tentative
    
    // Si l'utilisateur a atteint la limite de 3 tentatives
    if ($user->reset_attempts >= 3) {
        // Définir la fin du blocage à "maintenant + 3 heures"
        $blockTime = Carbon::now()->addHours(3);

        // Mettre à jour les champs de blocage
        $user->forceFill([
            'reset_attempts' => 0, // Optionnel: Réinitialiser le compteur
            'block_expires_at' => $blockTime,
        ])->save();
        
        // On ne procède pas à l'envoi, mais on renvoie succès pour masquer le statut
        return true;
    }


    $otpCode = random_int(100000, 999999); 
    $otpExpirationTime = Carbon::now()->addMinutes(10); 

    // 2. Stockage dans la table 'password_reset_tokens'
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token' => $otpCode, 
            'created_at' => $otpExpirationTime, 
        ]
    );

    // 3. Envoyer l'email
    Mail::to($user->email)->send(new ResetPasswordMail($otpCode));
    
   
    $user->increment('reset_attempts');

    return true;
}


    public function resetPassword($request)
    {
        // 1. Récupérer l'enregistrement de réinitialisation du mot de passe
        $resetRecord = DB::table('password_reset_tokens')
                           ->where('email', $request->email)
                           ->first();


        // 2. Vérifications du token et de l'expiration
        if (!$resetRecord || 
            !Hash::check($request->token, $resetRecord->token) ||
            Carbon::parse($resetRecord->created_at)->lessThan(now()->subHour(1))) 
        {
            return false;
        }

        // 3. Trouver l'utilisateur et mettre à jour le mot de passe
        $user = $this->auth->where('email', $request->email)->first();
        if (!$user) {
            return false;
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // 4. Supprimer l'entrée
        DB::table('password_reset_tokens')
          ->where('email', $request->email)
          ->delete();

        return $user;
    }

    public function verifyAccount(array $data): bool
{
    // 1. Trouver l'utilisateur
    $user = $this->auth->where('email', $data['email'])->first();

    // Si l'utilisateur n'existe pas ou est déjà vérifié
    if (!$user || $user->is_verified) {
        return false; 
    }
    
    // 2. Vérification du Code OTP
    
    // 🎯 Condition 1 : Le code fourni correspond-il au code stocké ?
    $isCodeValid = ($user->otp_code === $data['otp_code']);
    
    // 🎯 Condition 2 : Le code est-il expiré ?
    $isExpired = $user->otp_expires_at && Carbon::parse($user->otp_expires_at)->lessThan(Carbon::now());
    
    // Si le code ne correspond pas OU s'il est expiré
    if (!$isCodeValid || $isExpired) {
        // Tu peux choisir d'implémenter ici une logique d'échec de tentatives
        // pour bloquer temporairement l'utilisateur si nécessaire.
        return false;
    }

    // 3. Succès de la Validation : Vérification du Compte

    $user->is_verified = true;
    $user->email_verified_at = Carbon::now();
    $user->otp_code = null; 
    $user->otp_expires_at = null; 
    $user->save();

    return true; // Succès
}

    public function validateOtpAndResetPassword(array $data): bool
    {
        // 1. Trouver l'enregistrement OTP dans la table
        $resetData = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();
    
        // 2. Trouver l'utilisateur pour la mise à jour et la gestion des tentatives
        $user = $this->auth->where('email', $data['email'])->first();
    
        // Si aucune demande de réinitialisation n'existe ou si l'utilisateur est introuvable (ne devrait pas arriver avec la validation)
        if (!$resetData || !$user) {
            return false; // Échec
        }
        
        // 3. Vérifier la validité de l'OTP
    
        // 🎯 Condition 1 : Le code correspond-il ?
        $isCodeValid = ($resetData->token === $data['otp_code']);
        
        // 🎯 Condition 2 : Le code est-il expiré ?
        $isExpired = Carbon::parse($resetData->created_at)->lessThan(Carbon::now());
        
        // Si l'une des conditions n'est pas remplie
        if (!$isCodeValid || $isExpired) {
            $user->increment('reset_attempts');
            return false; // Échec
        }
    
       
        $user->password = Hash::make($data['password']);
        
        $user->reset_attempts = 0; 
        
        // Si tu utilises les champs OTP pour la vérification initiale, il est prudent de les effacer :
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();
    
        // Supprimer l'enregistrement de l'OTP de la table (très important !)
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
    
        return true;
    }



    public function resendVerificationOtp(string $email): bool
{
    $user = $this->auth->where('email', $email)->first();

    // 1. Vérifications de sécurité
    // Si l'utilisateur est introuvable (la validation l'a déjà vérifié, mais c'est une sécurité)
    if (!$user) {
        return false; 
    }
    
    // Si l'utilisateur est déjà vérifié, ne rien faire !
    if ($user->is_verified) {
        return false;
    }
    
    $newOtpCode = random_int(100000, 999999);
    $newOtpExpirationTime = Carbon::now()->addMinutes(10); 

    $user->forceFill([
        'otp_code' => $newOtpCode,
        'otp_expires_at' => $newOtpExpirationTime,
    ])->save();

    // 3. Envoi du nouvel e-mail
    Mail::to($user->email)->send(new sendOtpMail($newOtpCode));

    return true;
}
}



