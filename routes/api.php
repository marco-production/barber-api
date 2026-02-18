<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Auth routes without login
Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
    Route::post('/forgot-password', 'forgotPassword');
    //Route::post('/forgot-password-code', 'validateForgotPasswordCode' );
    //Route::post('/restore-password', 'restorePassword');

    //Account verification routes
    //Route::post('/email-verification', 'emailVerification');
    //Route::post('/phone-number-verification', 'phoneNumberVerification');
    //Route::post('/code-verification', 'codeVerification');
});

Route::post('/login/google', [SocialAuthController::class, 'loginWithGoogle']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
