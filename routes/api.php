<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//after login
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/register-type', [AuthController::class, 'registerType']);
Route::post('/login', [AuthController::class, 'login']);
//Authentication for all users
Route::middleware('tokenVerify')->group(function () {
    Route::post('/register-name', [AuthController::class, 'registerName']);
    Route::post('/register-phone', [AuthController::class, 'registerPhone']);
    Route::post('/user-verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/register-email', [AuthController::class, 'registerEmail']);
    Route::post('/register-password', [AuthController::class, 'registerPassword']);
    //vendor routes
    Route::middleware('userTypeVerify:vendor')->prefix('vendor')->group(function () {
        Route::post('/register', [AuthController::class, 'registerVendor']);
    });
    //driver routes
    Route::middleware('userTypeVerify:driver')->prefix('driver')->group(function () {
        Route::post('/register', [AuthController::class, 'registerDriver']);
    });
    Route::prefix('driver')->group(function () {
        Route::get('/', [DriverController::class, 'index']);
    });
    Route::prefix('vendor')->group(function () {
        Route::get('/', [VendorController::class, 'index']);
        Route::get('/search', [VendorController::class, 'searchByName']);
    });
});




