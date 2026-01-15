<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\NotificationController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Parts
    Route::get('/parts', [PartController::class, 'index']);
    Route::get('/parts/{partNumber}', [PartController::class, 'show']);
    Route::get('/parts/{partNumber}/stock', [\App\Http\Controllers\Api\OrderController::class, 'checkStock']);
    
    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/clear', [CartController::class, 'clear']);
        Route::post('/checkout', [\App\Http\Controllers\Api\OrderController::class, 'checkout']);
    });
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'history']);
    Route::get('/orders/{noSo}', [\App\Http\Controllers\Api\OrderController::class, 'detail']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/my-achievement', [CampaignController::class, 'myAchievement']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
