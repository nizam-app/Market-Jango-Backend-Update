<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//after login
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/verify-mail-otp', [AuthController::class, 'verifyMailOtp']);
Route::post('/register-type', [AuthController::class, 'registerType']);
Route::post('/login', [AuthController::class, 'login']);
// Route routes
Route::prefix('route')->group(function () {
    Route::get('/', [RouteController::class, 'index']);
    Route::post('/create', [RouteController::class, 'store']);
    Route::post('/update/{id}', [RouteController::class, 'update']);
    Route::post('/destroy/{id}', [RouteController::class, 'destroy']);
});
//banner routes
Route::prefix('banner')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::post('/create', [BannerController::class, 'store']);
    Route::post('/update/{id}', [BannerController::class, 'update']);
    Route::post('/destroy/{id}', [BannerController::class, 'destroy']);
});
//Authentication for all users
Route::middleware('tokenVerify')->group(function () {
    Route::post('/register-name', [AuthController::class, 'registerName']);
    Route::post('/register-phone', [AuthController::class, 'registerPhone']);
    Route::post('/user-verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/register-email', [AuthController::class, 'registerEmail']);
    Route::post('/register-password', [AuthController::class, 'registerPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    //vendor routes
    Route::middleware('userTypeVerify:vendor')->group(function () {
        //vendor routes
        Route::prefix('vendor')->group(function () {
            Route::post('/register', [AuthController::class, 'registerVendor']);
        });
        //category routes
        Route::prefix('category')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/create', [CategoryController::class, 'store']);
            Route::post('/update/{id}', [CategoryController::class, 'update']);
            Route::post('/destroy/{id}', [CategoryController::class, 'destroy']);
        });
        //product routes
        Route::prefix('product')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/create', [ProductController::class, 'store']);
            Route::post('/update/{id}', [ProductController::class, 'update']);
            Route::post('/destroy/{id}', [ProductController::class, 'destroy']);
        });
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




