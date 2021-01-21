<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JwtAuthController;
use App\Http\Controllers\CouponController;

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

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'coupons'
], function ($router) {
    Route::get('/', [CouponController::class, 'index']);
    Route::post('/', [CouponController::class, 'create']);
    // Route::post('/', [CouponController::class, 'create']);    
    // Route::put('/logout', [CouponController::class, 'logout']);
    // Route::post('/refresh', [CouponController::class, 'refresh']);
    // Route::get('/user', [CouponController::class, 'user']);    
});
