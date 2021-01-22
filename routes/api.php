<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtAuthController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\GiftController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('/login', [JwtAuthController::class, 'login']);
    Route::post('/register', [JwtAuthController::class, 'register']);    
    Route::post('/logout', [JwtAuthController::class, 'logout']);
    Route::post('/refresh', [JwtAuthController::class, 'refresh']);
    Route::get('/user', [JwtAuthController::class, 'user']);    
});

Route::middleware(['auth:api', 'throttle:6000,1'])->group( function () {
    Route::resource('coupons', CouponController::class);
    Route::post('coupons/{id}/init', [CouponController::class, 'init']);
    // Route::post('coupons/{id}/redeem', [CouponController::class, 'redeem']);
    
    Route::resource('gifts', GiftController::class);
    Route::post('gifts/{id}/redeem', [GiftController::class, 'redeem']);
});