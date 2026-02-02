<?php

use App\Http\Controllers\Api\Management\Access\Auth\SignInController;
use App\Http\Controllers\Api\Management\Access\Auth\SignUpController;
use App\Http\Controllers\Api\Management\Catalog\ProductController;
use App\Http\Controllers\Api\Management\Organization\Profiles\ProfileController;
use App\Http\Controllers\Api\Management\Organization\Stores\StoresController;
use App\Http\Controllers\Api\Management\Organization\Branches\BranchesController;
use App\Http\Controllers\Api\Management\Inventory\Warehouses\WarehousesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("auth")->group(function () {
    Route::post("signup", [SignUpController::class, "register"]);
    Route::post("signin", [SignInController::class, "signInWithEmailAndPassword"]);
    Route::post("signin/otp", [SignInController::class, "signInWithOtp"]);
    Route::post("signin/otp/generate", [SignInController::class, "generateOtp"]);
    Route::post("signout", [SignInController::class, "signOut"])->middleware('auth:sanctum');
});

Route::prefix("admin")->middleware(['auth:sanctum', 'profile.type:admin'])->group(function () {
    Route::prefix("profiles")->group(function () {
        Route::post("/", [ProfileController::class, "store"]);
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, "index"]);
        Route::get("/{id}", [ProductController::class, "show"]);
        Route::post("/single", [ProductController::class, "storeSingle"]);
        Route::post("/multiple", [ProductController::class, "storeMultiple"]);
        Route::post("/all", [ProductController::class, "storeAll"]);
    });

    Route::prefix("stores")->group(function () {
        Route::get("/", [StoresController::class, "index"]);
        Route::post("/", [StoresController::class, "store"]);
        Route::put("/{id}", [StoresController::class, "update"]);
        Route::get("/{id}", [StoresController::class, "show"]);
    });

    Route::prefix("branches")->group(function () {
        Route::get("/", [BranchesController::class, "index"]);
        Route::put("/{id}", [BranchesController::class, "update"]);
        Route::get("/{id}", [BranchesController::class, "show"]);
    });

    Route::prefix("warehouses")->group(function () {
        Route::put("/{id}", [WarehousesController::class, "update"]);
        Route::get("/{id}", [WarehousesController::class, "show"]);
    });
});