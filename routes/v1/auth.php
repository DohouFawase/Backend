<?php

use App\Http\Controllers\Authentication\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/sign-up', [AuthController::class, 'register']);
Route::post('/sign-in', [AuthController::class, 'login']);
Route::post('/forgot-password',[AuthController::class, 'forgotPassword'] );
Route::post('/verify-otp-reset', [AuthController::class, 'verifyOtpAndReset']);
Route::post('/verify-account', [AuthController::class, 'verifyAccount']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);