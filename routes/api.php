<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\Website\LoginController;
use App\Http\Controllers\API\Website\ProductController;
use App\Http\Controllers\API\Website\OrderController;
use App\Http\Controllers\API\Website\ReviewController;
use App\Http\Controllers\API\Website\CartController;
use App\Http\Controllers\API\Website\ProductAdminController;
use App\Http\Controllers\API\Website\AdminOrderController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
////////////////////////////////////////USER////////////////////////////////////////////////////////////
Route::post('login', [LoginController::class, 'login'])->name('login')->middleware('throttle:3,1');
Route::post('createUser', [LoginController::class, 'createUser'])->name('createUser')->middleware('throttle:60,1');
Route::post('verifyOtp', [LoginController::class, 'verifyOtp'])->name('verifyOtp')->middleware('throttle:3,1');
Route::post('resendOTP', [LoginController::class, 'resendOTP'])->name('resendOTP')->middleware('throttle:3,1');


////////////////////////////////////////Admin////////////////////////////////////////////////////////////
Route::post('loginAdmin', [LoginController::class, 'loginAdmin'])->name('loginAdmin')->middleware('throttle:60,1');
Route::get('getOrder', [AdminOrderController::class, 'getOrder'])->name('getOrder');



Route::middleware(['auth.api', 'throttle:60,1'])->group(function () {
    Route::get('getUser', [LoginController::class, 'getUser'])->name('getUser')->middleware('throttle:60,1');

    ///////////////////////////////////////////USER//////////////////////////////////////////////
    Route::get('userProfile', [LoginController::class, 'userProfile'])->name('userProfile')->middleware('throttle:60,1');
    Route::post('updateUserProfile', [LoginController::class, 'updateUserProfile'])->name('updateUserProfile')->middleware('throttle:60,1');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('throttle:3,1');
    Route::post('changePassword', [LoginController::class, 'changePassword'])->name('changePassword')->middleware('throttle:60,1');
    Route::get('viewOrderDetails', [OrderController::class, 'viewOrderDetails'])->name('viewOrderDetails')->middleware('throttle:60,1');
    Route::get('viewOrders', [OrderController::class, 'viewOrders'])->name('viewOrders')->middleware('throttle:60,1');

    ////////////////////////////////////////////REVIEW//////////////////////////////////////////////////////
    Route::post('createReview', [ReviewController::class, 'createReview'])->name('createReview')->middleware('throttle:60,1');
    Route::put('updateReviews/{id}', [ReviewController::class, 'updateReviews'])->name('updateReviews')->middleware('throttle:60,1');

    ///////////////////////////////////////////////CART//////////////////////////////////////////////////////////
    Route::middleware(['api'])->group(function () {
        Route::post('addToCart', [CartController::class, 'addToCart'])->name('addToCart')->middleware('throttle:60,1');
        Route::get('viewCart', [CartController::class, 'viewCart'])->name('viewCart')->middleware('throttle:60,1');
        Route::post('editCart', [CartController::class, 'editCart'])->name('editCart')->middleware('throttle:60,1');
        Route::delete('removeProductFromCart', [CartController::class, 'removeProductFromCart'])->name('removeProductFromCart')->middleware('throttle:60,1');
        Route::post('applyPromoCode', [CartController::class, 'applyPromoCode'])->name('applyPromoCode')->middleware('throttle:60,1');
        Route::delete('removePromoCode', [CartController::class, 'removePromoCode'])->name('removePromoCode')->middleware('throttle:60,1');
        Route::post('applyPoints', [CartController::class, 'applyPoints'])->name('applyPoints')->middleware('throttle:60,1');
        Route::delete('removePoints', [CartController::class, 'removePoints'])->name('removePoints')->middleware('throttle:60,1');
        Route::post('addShipmentInfo', [CartController::class, 'addShipmentInfo'])->name('addShipmentInfo')->middleware('throttle:60,1');
        Route::get('viewAllPreviousAdd', [CartController::class, 'viewAllPreviousAdd'])->name('viewAllPreviousAdd')->middleware('throttle:60,1');
    });
});

Route::post('addToCart', [CartController::class, 'addToCart'])->name('addToCart')->middleware('throttle:60,1');
Route::post('applyPromoCode', [CartController::class, 'applyPromoCode'])->name('applyPromoCode')->middleware('throttle:60,1');
Route::delete('removePromoCode', [CartController::class, 'removePromoCode'])->name('removePromoCode')->middleware('throttle:60,1');
Route::get('viewCart', [CartController::class, 'viewCart'])->name('viewCart')->middleware('throttle:60,1');
Route::post('editCart', [CartController::class, 'editCart'])->name('editCart')->middleware('throttle:60,1');
Route::delete('removeProductFromCart', [CartController::class, 'removeProductFromCart'])->name('removeProductFromCart')->middleware('throttle:60,1');
Route::post('addShipmentInfo', [CartController::class, 'addShipmentInfo'])->name('addShipmentInfo')->middleware('throttle:60,1');


////////////////////////////////////////PRODUCT///////////////////////////////////////////////////////////////
Route::get('getallproducts', [ProductController::class, 'getallproducts'])->name('getallproducts')->middleware('throttle:60,1');
Route::get('getproduct/{id}', [ProductController::class, 'getProduct'])->name('getproduct')->middleware('throttle:60,1');
Route::get('getFeaturedProducts', [ProductController::class, 'getFeaturedProducts'])->name('getFeaturedProducts')->middleware('throttle:60,1');
Route::get('filterProducts', [ProductController::class, 'filterProducts'])->name('filterProducts')->middleware('throttle:60,1');
Route::get('searchProducts', [ProductController::class, 'searchProducts'])->name('searchProducts')->middleware('throttle:60,1');
Route::get('searchhints', [ProductController::class, 'searchhints'])->name('searchhints')->middleware('throttle:60,1');



Route::post('createOrder', [OrderController::class, 'createOrder'])->name('createOrder')->middleware('throttle:60,1');
///////////////////////////////////////////admin////////////////////////////////////////////////////////
Route::get('getAllProductsAdmin', [ProductAdminController::class, 'getAllProductsAdmin'])->name('getAllProductsAdmin')->middleware('throttle:60,1');
Route::get('getAllTypeIdAdmin', [ProductAdminController::class, 'getAllTypeIdAdmin'])->name('getAllTypeIdAdmin')->middleware('throttle:60,1');
Route::post('editProductAdmin', [ProductAdminController::class, 'editProductAdmin'])->name('editProductAdmin')->middleware('throttle:60,1');
Route::get('getProductAdmin', [ProductAdminController::class, 'getProductAdmin'])->name('getProductAdmin')->middleware('throttle:60,1');
Route::post('addNewProductAdmin', [ProductAdminController::class, 'addNewProductAdmin'])->name('addNewProductAdmin')->middleware('throttle:60,1');


