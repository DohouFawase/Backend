<?php

namespace App\Http\Repositories\Authentication;

use App\Mail\LoginVerifcationMail;
use App\Mail\ResetPasswordMail;
use App\Mail\sendOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthRepository
{
    public function __construct(private User $auth) {}

    // ------------------------------------------
    // A. User Registration
    // ------------------------------------------
    public function UserRegister($request)
    {
        $createRandomOtpNumber = random_int(100000, 999999);
        $hashedPassword = Hash::make($request->password);
        $otpExpirationTime = Carbon::now()->addMinutes(10);

        $cretateUser = $this->auth->create([
            'email' => $request->email,
            'password' => $hashedPassword,
            'otp_code' => $createRandomOtpNumber,
            'otp_expires_at' => $otpExpirationTime,
            'is_verified' => false,
        ]);

        Mail::to($cretateUser->email)->send(new sendOtpMail($createRandomOtpNumber));

        return $cretateUser;
    }

    // ------------------------------------------
    // B. User Login (send OTP for verification)
    // ------------------------------------------
    // public function login($request)
    // {
    //     $user = $this->auth->where('email', $request->email)->first();
    //     if (!$user || !Hash::check($request->password, $user->password)) {
    //         return false;
    //     }

    //     if (isset($user->is_verified) && !$user->is_verified) {
    //         return 'unverified';
    //     }

    //     $loginOtpCode = random_int(100000, 999999);
    //     $otpExpirationTime = Carbon::now()->addMinutes(5);

    //     $user->forceFill([
    //         'otp_code' => $loginOtpCode,
    //         'otp_expires_at' => $otpExpirationTime,
    //     ])->save();

    //     Mail::to($user->email)->send(new LoginVerifcationMail($loginOtpCode));
    // }


    // Dans AuthRepository.php - MODIFIER la méthode login

public function login($request)
{
    $user = $this->auth->where('email', $request->email)->first();
    
    if (!$user) {
        return ['status' => 'not_found'];
    }

    if (isset($user->is_verified) && !$user->is_verified) {
        return ['status' => 'unverified'];
    }

    // Si l'utilisateur choisit l'authentification par OTP
    if ($request->use_otp === true) {
        $loginOtpCode = random_int(100000, 999999);
        $otpExpirationTime = Carbon::now()->addMinutes(5);

        $user->forceFill([
            'otp_code' => $loginOtpCode,
            'otp_expires_at' => $otpExpirationTime,
        ])->save();

        Mail::to($user->email)->send(new LoginVerifcationMail($loginOtpCode));
        
        return ['status' => 'otp_sent'];
    }

    // Si l'utilisateur choisit l'authentification par mot de passe
    if ($request->password) {
        if (!Hash::check($request->password, $user->password)) {
            return ['status' => 'invalid_password'];
        }
        
        $token = $user->createToken('authToken')->plainTextToken;
        $user->update(['last_login' => Carbon::now()]);
        
        return [
            'status' => 'success',
            'user' => $user,
            'token' => $token
        ];
    }

    return ['status' => 'method_required'];
}

    // ------------------------------------------
    // C. Validate Login OTP & Issue Token
    // ------------------------------------------
    public function validateLoginOtpAndIssueToken(array $data): array|false
    {
        $user = $this->auth->where('email', $data['email'])->first();
        if (!$user) {
            return false;
        }

        $isCodeValid = ($user->otp_code === $data['otp_code']);
        $isExpired = $user->otp_expires_at && Carbon::parse($user->otp_expires_at)->lessThan(Carbon::now());

        if (!$isCodeValid || $isExpired) {
            return false;
        }

        $user->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
            'last_login' => Carbon::now(),
        ])->save();

        $token = $user->createToken('authToken')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    // ------------------------------------------
    // D. Logout
    // ------------------------------------------
    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
        return true;
    }

    // ------------------------------------------
    // E. Forgot Password (send OTP with limit)
    // ------------------------------------------
    public function forgotPassword($request)
    {
        $user = $this->auth->where('email', $request->email)->first();

        if (!$user) {
            return true;
        }

        if ($user->block_expires_at && Carbon::now()->lessThan($user->block_expires_at)) {
            return true;
        }

        if ($user->reset_attempts >= 3) {
            $blockTime = Carbon::now()->addHours(3);
            $user->forceFill([
                'reset_attempts' => 0,
                'block_expires_at' => $blockTime,
            ])->save();
            return true;
        }

        $otpCode = random_int(100000, 999999);
        $otpExpirationTime = Carbon::now()->addMinutes(10);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otpCode,
                'created_at' => $otpExpirationTime,
            ]
        );

        Mail::to($user->email)->send(new ResetPasswordMail($otpCode));
        $user->increment('reset_attempts');

        return true;
    }

    // ------------------------------------------
    // F. Reset Password after validation
    // ------------------------------------------
    public function resetPassword($request)
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (
            !$resetRecord ||
            !Hash::check($request->token, $resetRecord->token) ||
            Carbon::parse($resetRecord->created_at)->lessThan(now()->subHour(1))
        ) {
            return false;
        }

        $user = $this->auth->where('email', $request->email)->first();
        if (!$user) {
            return false;
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return $user;
    }

    // ------------------------------------------
    // G. Verify account (OTP confirmation)
    // ------------------------------------------
    public function verifyAccount(array $data): bool
    {
        $user = $this->auth->where('email', $data['email'])->first();

        if (!$user || $user->is_verified) {
            return false;
        }

        $isCodeValid = ($user->otp_code === $data['otp_code']);
        $isExpired = $user->otp_expires_at && Carbon::parse($user->otp_expires_at)->lessThan(Carbon::now());

        if (!$isCodeValid || $isExpired) {
            return false;
        }

        $user->is_verified = true;
        $user->email_verified_at = Carbon::now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return true;
    }

    // ------------------------------------------
    // H. Validate OTP and Reset Password
    // ------------------------------------------
    public function validateOtpAndResetPassword(array $data): bool
    {
        $resetData = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        $user = $this->auth->where('email', $data['email'])->first();

        if (!$resetData || !$user) {
            return false;
        }

        $isCodeValid = ($resetData->token === $data['otp_code']);
        $isExpired = Carbon::parse($resetData->created_at)->lessThan(Carbon::now());

        if (!$isCodeValid || $isExpired) {
            $user->increment('reset_attempts');
            return false;
        }

        $user->password = Hash::make($data['password']);
        $user->reset_attempts = 0;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return true;
    }

    // ------------------------------------------
    // I. Resend verification OTP
    // ------------------------------------------
    public function resendVerificationOtp(string $email): bool
    {
        $user = $this->auth->where('email', $email)->first();

        if (!$user || $user->is_verified) {
            return false;
        }

        $newOtpCode = random_int(100000, 999999);
        $newOtpExpirationTime = Carbon::now()->addMinutes(10);

        $user->forceFill([
            'otp_code' => $newOtpCode,
            'otp_expires_at' => $newOtpExpirationTime,
        ])->save();

        Mail::to($user->email)->send(new sendOtpMail($newOtpCode));

        return true;
    }




    public function checkEmail(string $email): array|false
{
    $user = $this->auth->where('email', $email)->first();

    if (!$user) {
        return false;
    }

    if (!$user->is_verified) {
        return [
            'status' => 'unverified',
            'user' => $user
        ];
    }

    // Générer et envoyer l'OTP de connexion
    $loginOtpCode = random_int(100000, 999999);
    $otpExpirationTime = Carbon::now()->addMinutes(5);

    $user->forceFill([
        'otp_code' => $loginOtpCode,
        'otp_expires_at' => $otpExpirationTime,
    ])->save();

    Mail::to($user->email)->send(new LoginVerifcationMail($loginOtpCode));

    return [
        'status' => 'otp_sent',
        'user' => $user
    ];
}
}
