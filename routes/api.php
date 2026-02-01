<?php

use App\Http\Controllers\Api\Management\Access\Auth\SignInController as AdminSignInController;
use App\Http\Controllers\Api\Management\Access\Auth\SignUpController as AdminSignUpController;
use App\Http\Controllers\Api\Management\Catalog\ProductController as AdminProductController;
use App\Http\Controllers\Api\Management\Organization\Profiles\ProfileController as AdminProfileController;
use App\Http\Controllers\Api\Management\Organization\Stores\StoresController as AdminStoresController;
use App\Http\Controllers\Api\Management\Organization\Branches\BranchesController as AdminBranchesController;
use App\Http\Controllers\Api\Management\Inventory\Warehouses\WarehousesController as AdminWarehousesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("auth")->group(function () {
    Route::post("signup", [AdminSignUpController::class, "register"]);
    Route::post("signin", [AdminSignInController::class, "signInWithEmailAndPassword"]);
    Route::post("signin/otp", [AdminSignInController::class, "signInWithOtp"]);
    Route::post("signin/otp/generate", [AdminSignInController::class, "generateOtp"]);
    Route::post("signout", [AdminSignInController::class, "signOut"])->middleware('auth:sanctum');
});

Route::prefix("admin")->middleware(['auth:sanctum', 'profile.type:admin'])->group(function () {
    Route::prefix("profiles")->group(function () {
        Route::post("/", [AdminProfileController::class, "store"]);
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [AdminProductController::class, "index"]);
        Route::get("/{id}", [AdminProductController::class, "show"]);
        Route::post("/single", [AdminProductController::class, "storeSingle"]);
        Route::post("/multiple", [AdminProductController::class, "storeMultiple"]);
        Route::post("/all", [AdminProductController::class, "storeAll"]);
    });

    Route::prefix("stores")->group(function () {
        Route::get("/", [AdminStoresController::class, "index"]);
        Route::post("/", [AdminStoresController::class, "store"]);
        Route::put("/{id}", [AdminStoresController::class, "update"]);
        Route::get("/{id}", [AdminStoresController::class, "show"]);
    });

    Route::prefix("branches")->group(function () {
        Route::get("/", [AdminBranchesController::class, "index"]);
        Route::put("/{id}", [AdminBranchesController::class, "update"]);
        Route::get("/{id}", [AdminBranchesController::class, "show"]);
    });

    Route::prefix("warehouses")->group(function () {
        Route::put("/{id}", [AdminWarehousesController::class, "update"]);
        Route::get("/{id}", [AdminWarehousesController::class, "show"]);
    });
});