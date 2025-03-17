<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\API\OrderItemController;

// User route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API resources
Route::apiResource('products', ProductController::class);
Route::apiResource('orders', OrderController::class);
Route::get('orders/search', [OrderController::class, 'search']);

// Order items nested routes
Route::apiResource('orders.items', OrderItemController::class);
