<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockMutationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);

Route::get('products/{product}/mutations', [StockMutationController::class, 'index']);
Route::post('products/{product}/stock', [StockMutationController::class, 'store']);
