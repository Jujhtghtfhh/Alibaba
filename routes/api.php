<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

/*
| Every route here is prefixed with /api (see bootstrap/app.php).
| Run "php artisan route:list --path=api" to see them all.
|
| Who can do what:
| - guest (no token):  read everything (LIST and READ, dashboard), register, login, refresh
| - user  (role user):  + create items and reviews, update / delete only their own
| - admin (role admin): + create, update, delete categories, update / delete any item or review
*/

Route::name('api.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    });

    // Open to everyone, including guests.
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('items', ItemController::class)->only(['index', 'show']);
    Route::apiResource('reviews', ReviewController::class)->only(['index', 'show']);

    // Logged-in users only ("Authorization: Bearer <access token>").
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('items', ItemController::class)->except(['index', 'show']);
        Route::apiResource('reviews', ReviewController::class)->except(['index', 'show']);

        // Admins only.
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show'])->middleware('role:admin');
    });
});
