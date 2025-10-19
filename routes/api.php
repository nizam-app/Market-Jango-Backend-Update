<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BuyerHomeController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\VariantValueController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VendorHomePageController;
use App\Http\Controllers\Api\WishListController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//after login
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/verify-mail-otp', [AuthController::class, 'verifyMailOtp']);
Route::post('/register-type', [AuthController::class, 'registerType']);
Route::post('/login', [AuthController::class, 'login']);
//Home Buyer all routes
Route::get('/product/filter', [BuyerHomeController::class, 'productFilter']);
Route::get('/category', [CategoryController::class, 'index']);

Route::get('/driver', [DriverController::class, 'index']);
Route::get('/user', [UserController::class, 'index']);
Route::get('/user/{id}', [UserController::class, 'show']);

Route::post('/chat/send', [ChatController::class, 'sendMessage']);
Route::post('/chat/history', [ChatController::class, 'getMessages']);
// Admin all routes
Route::get('/active/vendor', [AdminController::class, 'activeVendor']);
Route::get('/pending/vendor', [AdminController::class, 'pendingVendor']);
Route::get('/suspended/vendor', [AdminController::class, 'suspendedVendor']);
Route::get('/accept/vendor/{vendor_id}', [AdminController::class, 'acceptOrRejectVendor']);
//Route::middleware('userTypeVerify:admin')->group(function () {
//
//});
// Route routes
Route::prefix('route')->group(function () {
    Route::get('/', [RouteController::class, 'index']);
    Route::get('/show', [RouteController::class, 'show']);
    Route::post('/create', [RouteController::class, 'store']);
    Route::post('/update/{id}', [RouteController::class, 'update']);
    Route::post('/destroy/{id}', [RouteController::class, 'destroy']);
});
//locations routes
Route::prefix('location')->group(function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::get('/show', [LocationController::class, 'show']);
    Route::post('/create', [LocationController::class, 'store']);
    Route::post('/update/{id}', [LocationController::class, 'update']);
    Route::post('/destroy/{id}', [LocationController::class, 'destroy']);
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
        Route::middleware('statusVerify')->group(function () {
            //vendor routes
            Route::prefix('vendor')->group(function () {
                Route::post('/register', [AuthController::class, 'registerVendor']);
                Route::get('/search-by-vendor', [VendorHomePageController::class, 'productSearchByVendor']);
                Route::get('/product', [VendorHomePageController::class, 'vendorProduct']);
                Route::get('/show', [VendorHomePageController::class, 'show']);
                Route::get('/category', [VendorController::class, 'category']);
                Route::get('/attribute', [VendorController::class, 'attribute']);
            });
            //category routes
            Route::prefix('user')->group(function () {
                Route::post('/update', [VendorHomePageController::class, 'update']);
            });

            //category routes
            Route::prefix('category')->group(function () {
                Route::post('/create', [CategoryController::class, 'store']);
                Route::post('/update/{id}', [CategoryController::class, 'update']);
                Route::post('/destroy/{id}', [CategoryController::class, 'destroy']);
            });
            //product Variant routes
            Route::prefix('product-attribute')->group(function () {
                Route::get('/', [ProductVariantController::class, 'index']);
                Route::get('/show', [ProductVariantController::class, 'show']);
                Route::post('/create', [ProductVariantController::class, 'store']);
                Route::post('/update/{id}', [ProductVariantController::class, 'update']);
                Route::post('/destroy/{id}', [ProductVariantController::class, 'destroy']);
            });
            // Variant Value routes
            Route::prefix('attribute-value')->group(function () {
                Route::get('/', [VariantValueController::class, 'index']);
                Route::post('/create', [VariantValueController::class, 'store']);
                Route::post('/update/{id}', [VariantValueController::class, 'update']);
                Route::post('/destroy/{id}', [VariantValueController::class, 'destroy']);
            });
            //product routes
            Route::prefix('product')->group(function () {
                Route::get('/', [ProductController::class, 'index']);
                Route::post('/create', [ProductController::class, 'store']);
                Route::post('/update/{id}', [ProductController::class, 'update']);
                Route::post('/destroy/{id}', [ProductController::class, 'destroy']);
            });
        });
    });
    //Buyer routes
    Route::middleware('userTypeVerify:buyer')->group(function () {
        // Cart routes
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/create', [CartController::class, 'store']);
            Route::post('/checkout', [CartController::class, 'checkout']);
        });
        // wish-list routes
        Route::prefix('wish-list')->group(function () {
            Route::get('/', [WishListController::class, 'index']);
            Route::post('/create-update', [WishListController::class, 'store']);
            Route::post('/destroy/{wishlist_id}', [WishListController::class, 'destroy']);
        });
        // review routes
        Route::prefix('review')->group(function () {
            Route::get('/', [ReviewController::class, 'index']);
            Route::post('/create-update', [ReviewController::class, 'store']);
            Route::post('/destroy/{review_id}', [ReviewController::class, 'destroy']);
        });
    });
    //driver routes
    Route::middleware('userTypeVerify:driver')->prefix('driver')->group(function () {
        Route::post('/register', [AuthController::class, 'registerDriver']);
    });
    Route::prefix('driver')->group(function () {
    });
    Route::prefix('vendor')->group(function () {
//        Route::get('/', [VendorController::class, 'index']);
        Route::get('/search', [VendorController::class, 'searchByName']);
    });
});




