<?php

use App\Http\Controllers\Admin\Auth\SignInController as AdminSignInController;
use App\Http\Controllers\Admin\Auth\SignUpController as AdminSignUpController;
use App\Http\Controllers\Admin\Products\ProductController as AdminProductController;
use App\Http\Controllers\Admin\Profiles\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\Stores\StoresController as AdminStoresController;
use App\Http\Controllers\Admin\Branches\BranchesController as AdminBranchesController;
use App\Http\Controllers\Admin\Warehouses\WarehousesController as AdminWarehousesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Admin\EnsureAdminMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("auth")->group(function () {
    Route::prefix("admin")->group(function () {
        Route::post("signup", [AdminSignUpController::class, "register"]);
        Route::post("signin", [AdminSignInController::class, "signInWithEmailAndPassword"]);
        Route::post("signin/otp", [AdminSignInController::class, "signInWithOtp"]);
        Route::post("signin/otp/generate", [AdminSignInController::class, "generateOtp"]);
        Route::post("signout", [AdminSignInController::class, "signOut"]);
    });
});

Route::prefix("admin")->middleware(['auth:sanctum', 'profile.type:admin'])->group(function () {
    Route::prefix("profiles")->group(function () {
        Route::post("/", [AdminProfileController::class, "store"]);
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [AdminProductController::class, "index"]);
        Route::get("/{id}", [AdminProductController::class, "show"]);
        Route::post("/", [AdminProductController::class, "store"]);
    });

    Route::prefix("stores")->group(function () {
        Route::put("/{id}", [AdminStoresController::class, "update"]);
        Route::get("/{id}", [AdminStoresController::class, "show"]);
    });

    Route::prefix("branches")->group(function () {
        Route::put("/{id}", [AdminBranchesController::class, "update"]);
        Route::get("/{id}", [AdminBranchesController::class, "show"]);
    });

    Route::prefix("warehouses")->group(function () {
        Route::put("/{id}", [AdminWarehousesController::class, "update"]);
        Route::get("/{id}", [AdminWarehousesController::class, "show"]);
    });
});