<?php

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminSelectController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BuyerHomeController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DeliveryChargeController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverHomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\TransportHomeController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\VariantValueController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VendorHomePageController;
use App\Http\Controllers\Api\WishListController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

//after login
Route::post('/register-type', [AuthController::class, 'registerType']);
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::get('/business-type', [AuthController::class, 'businessType']);
Route::post('/verify-mail-otp', [AuthController::class, 'verifyMailOtp']);
Route::post('/login', [AuthController::class, 'login']);
// Payment callback routes
Route::get("/payment/response", [InvoiceController::class, 'handleFlutterWaveResponse']);



//Authentication for all users
Route::middleware(['tokenVerify'])->group(function () {
    Route::get('/translations', function (Request $request) {
        $user_id = $request->header('id');

        $user = User::where('id', $user_id)->first();
        if (!$user) {
            return ResponseHelper::Out('failed', 'User not found', null, 404);
        }
        $lang = $request->query('lang', $user->language ?? 'en');
        App::setLocale($lang);
        $translations = trans('messages');
        return response()->json($translations);
    });
    Route::post('/user/heartbeat', [AuthController::class, 'heartbeat']);
    Route::post('/user/{id}/status', [AuthController::class, 'getstatus']);

    Route::prefix('review')->group(function () {
        Route::get("/buyer", [ReviewController::class, 'buyerReview']);
        Route::post('/create/buyer/{id}', [ReviewController::class, 'createBuyerReview']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'updateRoles']);
        Route::delete('/{id}', [RoleController::class, 'destroyRoles']);
        Route::post('/{role_id}/permissions', [RoleController::class, 'assignPermissions']);
    });
    Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::put('/{id}', [PermissionController::class, 'update']);
    Route::delete('/{id}', [PermissionController::class, 'destroy']);

    });
    Route::post('/users/{user_id}/assign-role', [UserRoleController::class, 'assignRole']);
    Route::delete('/users/{user_id}/remove-role', [UserRoleController::class, 'removeRole']);
    Route::get('/users/{user_id}/permissions', [UserRoleController::class, 'getUserPermissions']);

    Route::post('/register-name', [AuthController::class, 'registerName']);
    Route::post('/register-phone', [AuthController::class, 'registerPhone']); // not complete
    Route::post('/user-verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/register-email', [AuthController::class, 'registerEmail']);
    Route::post('/register-password', [AuthController::class, 'registerPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/vendor/list/{id}', [BuyerHomeController::class, 'vendorListId']);
    Route::get('/vendor/first/product', [BuyerHomeController::class, 'vendorFirstProduct']);
    Route::get('/popular/product/{id}', [BuyerHomeController::class, 'popularProducts']);
    Route::get('/language', [AuthController::class, 'language']);
//    Route::get('/drivers/filter',        [AdminController::class, 'driverFilter']);
//    Route::get('/drivers/{id}',   [AdminController::class, 'driverDetails']);
    // Fetch all buyer home page products
    Route::prefix('admin-selects')->group(function () {
        Route::get('top-categories', [AdminSelectController::class, 'getTopCategory']);
        Route::get('top-products', [ProductController::class, 'getTopProduct']);
        Route::get('new-items', [ProductController::class, 'getNewItem']);
        Route::get('just-for-you', [ProductController::class, 'getJustForYou']);
    });
    //user Update routes
    Route::prefix('user')->group(function () {
        Route::post('/update', [AuthController::class, 'update']);
    });
    //Invoice and payment
    Route::get("/invoice", [InvoiceController::class, 'index']);
    Route::get("/buyer/all-order", [InvoiceController::class, 'allOrders']);
    Route::get("/all-order/transport", [TransportHomeController::class, 'allOrdersTransport']);
    Route::get("/all-order/driver", [DriverHomeController::class, 'allOrdersDriver']);
    Route::post("/invoice/create", [InvoiceController::class, 'InvoiceCreate']);
    Route::get("/InvoiceProductList/{invoice_id}", [InvoiceController::class, 'InvoiceProductList']);

    //Home Buyer all routes
    Route::get('/product/filter', [BuyerHomeController::class, 'productFilter']); // not complete
    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/driver', [DriverController::class, 'index']);
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/user/detail', [UserController::class, 'userDetail']);
    Route::get('/user/show', [UserController::class, 'show']);
    Route::get('/product', [ProductController::class, 'index']);
    Route::get('/product/detail/{id}', [ProductController::class, 'productDetails']);
    Route::get('/product/vendor/{id}', [BuyerHomeController::class, 'vendorByProduct']);
    Route::get('/category/vendor/product/{id}', [BuyerHomeController::class, 'vendorCategoryByProduct']);

    //Driver Home Page Routes
//    Route::get('/driver/total-order/count', [DriverHomeController::class, 'driverTotalOrderCount']);

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
        Route::delete('/destroy/{id}', [LocationController::class, 'destroy']);
    });
    //banner routes
    Route::prefix('banner')->group(function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::post('/create', [BannerController::class, 'store']);
        Route::post('/update/{id}', [BannerController::class, 'update']);
        Route::delete('/destroy/{id}', [BannerController::class, 'destroy']);
    });
    //chat routes
    Route::prefix('chat')->group(function () {
        Route::get('/user', [ChatController::class, 'userInbox']);
        Route::get('/user/search', [ChatController::class, 'userSearch']);
        Route::post('/send/{id}', [ChatController::class, 'sendMessage']); // not complete
        Route::get('/history/{id}', [ChatController::class, 'getMessages']); // not complete
    });
    // notifications routes
    Route::prefix('notification')->group(function () {
        Route::get('/', [NotificationController::class, 'myNotifications']);
        Route::get('/read/{id}', [NotificationController::class, 'markAsRead']);
    });
    //vendor routes
    Route::middleware('userTypeVerify:vendor')->group(function () {
        Route::post('/vendor/register', [AuthController::class, 'registerVendor']);
        Route::middleware('statusVerify')->group(function () {
            //Vendor Home Page
            Route::get('/drivers/search', [VendorHomePageController::class, 'driverSearch']);
            Route::get('/vendor/all/order', [VendorHomePageController::class, 'vendorAllOrder']);
            Route::post('/vendor/invoice/create/{driver_id}/{order_item_id}', [VendorHomePageController::class, 'vendorInvoice']);

            //vendor routes
            Route::prefix('vendor')->group(function () {
                Route::get('/product', [VendorHomePageController::class, 'vendorProduct']);
                Route::get('/search-by-vendor', [VendorHomePageController::class, 'productSearchByVendor']);
                Route::post('/image/destroy/{id}', [ProductController::class, 'vendorProductImageDestroy']);
                Route::get('/show', [VendorHomePageController::class, 'show']);
                Route::get('/category/product/{id}', [VendorController::class, 'vendorCategoryWiseProduct']);
                Route::get('/category', [VendorController::class, 'category']);
                Route::get('/category/{id}', [VendorController::class, 'categoryByProduct']);
                Route::get('/attribute', [VendorController::class, 'attribute']);
            });
            //category routes
            Route::prefix('category')->group(function () {
                Route::post('/create', [CategoryController::class, 'store']);
                Route::post('/update/{id}', [CategoryController::class, 'update']);
                Route::post('/destroy/{id}', [CategoryController::class, 'destroy']);
            });
            //product attribute routes
            Route::prefix('product-attribute')->group(function () {
                Route::get('/vendor', [ProductVariantController::class, 'allAttributes']);
                Route::get('/vendor/show', [ProductVariantController::class, 'show']);
                Route::post('/create', [ProductVariantController::class, 'store']);
                Route::post('/update/{id}', [ProductVariantController::class, 'update']);
                Route::post('/destroy/{id}', [ProductVariantController::class, 'destroy']);
            });
            // Variant Value routes
            Route::prefix('attribute-value')->group(function () {
                Route::get('/vendor', [VariantValueController::class, 'allAttributeValues']);
                Route::post('/create', [VariantValueController::class, 'store']);
                Route::post('/update/{id}', [VariantValueController::class, 'update']);
                Route::post('/destroy/{id}', [VariantValueController::class, 'destroy']);
            });
            //product Variant routes
            Route::prefix('delivery-charge')->group(function () {
                Route::get('/vendor', [DeliveryChargeController::class, 'allDeliveryCharges']);
                Route::get('/show', [DeliveryChargeController::class, 'show']);
                Route::post('/create', [DeliveryChargeController::class, 'store']);
                Route::post('/update/{id}', [DeliveryChargeController::class, 'update']);
                Route::post('/destroy/{id}', [DeliveryChargeController::class, 'destroy']);
            });
            //product routes
            Route::prefix('product')->group(function () {
                Route::post('/create', [ProductController::class, 'store']);
                Route::post('/update/{id}', [ProductController::class, 'update']);
                Route::post('/destroy/{id}', [ProductController::class, 'destroy']);
            });
        });
    });
    //Buyer routes
    Route::middleware('userTypeVerify:buyer')->group(function () {
        Route::get('/search/product', [BuyerHomeController::class, 'productSearchByBuyer']);
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
            Route::post('/destroy/{review_id}', [ReviewController::class, 'destroy']);
        });
    });
    //driver routes
    Route::middleware('userTypeVerify:driver')->prefix('driver')->group(function () {
        Route::post('/register', [AuthController::class, 'registerDriver']);
    });
    Route::get('/approved-driver', [AdminController::class, 'approvedDriver']);
    Route::get('/driver/home-stats', [DriverHomeController::class, 'driverHomeStats']);

    //Admin Routes
    Route::middleware('userTypeVerify:admin')->group(function () {
        Route::post('/admin/invoice/create/{driver_id}/{order_item_id}', [AdminController::class, 'adminInvoice']);
        Route::post('/create-admin', [AdminController::class, 'createAdmin']);
        Route::get('/active/vendor', [AdminController::class, 'activeVendor']);
        Route::get('/pending/vendor', [AdminController::class, 'pendingVendor']);
        Route::get('/admin', [AdminController::class, 'index']);
        Route::get('/suspended/vendor', [AdminController::class, 'suspendedVendor']);
        Route::put('/vendor/status-update/{vendor_id}', [AdminController::class, 'vendorStatusUpdate']);
        Route::put('/driver/status-update/{driver_id}', [AdminController::class, 'driverStatusUpdate']);
        Route::post('/product-status-update/{id}', [AdminController::class, 'productStatusUpdate']);
        Route::get('/vendor-request-count', [AdminController::class, 'vendorRequestCount']);
        Route::get('/vendor-count', [AdminController::class, 'vendorCount']);
        Route::get('/not/delivered/order', [AdminController::class, 'notDeliveredOrder']);
        Route::get('/driver-request-count', [AdminController::class, 'driverRequestCount']);
        Route::get('/driver-count', [AdminController::class, 'driverCount']);
        Route::get('/request-product', [AdminController::class, 'requestProduct']);
        Route::get('/request-product-details/{id}', [AdminController::class, 'requestProductDetails']);
        Route::get('/request-driver', [AdminController::class, 'requestDriver']);
        Route::get('/request-driver/show', [AdminController::class, 'requestDriverDetails']);
        Route::get('/suspended-driver', [AdminController::class, 'suspendedDriver']);
        Route::get('/suspended-driver/show', [AdminController::class, 'suspendedDriverDetails']);
        Route::put('/admin-select-update/{id}', [AdminSelectController::class, 'adminSelectUpdate']);
        Route::get('/admin-product', [ProductController::class, 'adminProduct']);
        Route::put('/admin-category-update/{id}', [AdminSelectController::class, 'adminCategoryUpdate']);
        Route::delete('/user/destroy/{id}', [AdminController::class, 'destroy']);
        Route::get('/all/order', [AdminController::class, 'allOrder']);
    });
        Route::get('/drivers/search/location', [DriverHomeController::class, 'driverSearchByLocation']);

    Route::middleware('userTypeVerify:transport')->group(function () {

    });

    Route::post("/transport/invoice/create/{driver_id}", [TransportHomeController::class, 'InvoiceCreateTransport']);
    //search
    Route::get('/search/product', [BuyerHomeController::class, 'productSearchByBuyer']);
    Route::get('/vendor/details/{id}', [TransportHomeController::class, 'driverDetails']);
    Route::get('/transport/invoice/tracking/{id}', [TransportHomeController::class, 'showTransportTracking']);
    Route::get('/transport/successful/invoice/details/{id}', [TransportHomeController::class, 'showSuccessfulTransportTracking']);
    Route::get('/transport/cancel/invoice/tracking/{id}', [TransportHomeController::class, 'showCancelTransportTracking']);
    Route::get('/driver/invoice/pending/tracking/{id}', [DriverHomeController::class, 'showDriverTracking']);
    Route::get('/driver/invoice/tracking/{id}', [DriverHomeController::class, 'DriverTracking']);
    Route::get('/driver/successful/invoice/tracking/{id}', [DriverHomeController::class, 'showSuccessfulDriverTracking']);
    Route::get('/driver/cancel/invoice/tracking/{id}', [DriverHomeController::class, 'showCancelDriverTracking']);

    Route::get('/buyer/invoice/tracking/details/{id}', [InvoiceController::class, 'showTrackingBuyerDetails']);
    Route::put('/invoice/update-status/{id}', [InvoiceController::class, 'updateStatus']);

    Route::prefix('driver')->group(function () {
        Route::get('/show', [AdminController::class, 'driverDetails']);
    });
    Route::prefix('vendor')->group(function () {
        Route::get('/', [VendorController::class, 'index']);
        Route::get('/search', [VendorController::class, 'searchByName']);
    });
});




