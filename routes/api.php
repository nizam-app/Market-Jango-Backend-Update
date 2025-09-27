<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//Authentication for all users
Route::get('/register-type', [AuthController::class, 'types']);
Route::post('/register-type', [AuthController::class, 'registerType']);
Route::post('/register-title', [AuthController::class, 'registerTitle']);
Route::post('/register-phone', [AuthController::class, 'registerPhone']);
Route::post('/register-email', [AuthController::class, 'registerEmail']);
Route::post('/register-verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/register-password', [AuthController::class, 'registerPassword']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/register-driver', [AuthController::class, 'registerDriver']);
Route::post('/login', [AuthController::class, 'login']);

//after login
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
