<?php

use App\Http\Controllers\Api\Management\Access\Auth\SignInController;
use App\Http\Controllers\Api\Management\Access\Auth\SignUpController;
use App\Http\Controllers\Api\Management\Catalog\ProductController;
use App\Http\Controllers\Api\Management\DashboardController;
use App\Http\Controllers\Api\Management\Organization\Profiles\ProfileController;
use App\Http\Controllers\Api\Management\Organization\Stores\StoresController;
use App\Http\Controllers\Api\Management\Organization\Branches\BranchesController;
use App\Http\Controllers\Api\Management\Inventory\Warehouses\WarehousesController;
use App\Http\Controllers\Api\Management\Inventory\StorageLocationController;
use App\Http\Controllers\Api\Management\Catalog\CategoryController;
use App\Http\Controllers\Api\Management\Inventory\Stock\StockController;
use App\Http\Controllers\Api\Management\ActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user()->load('profile');
    $user->permissions = $user->getAllPermissionsAbilities();
    return $user;
})->middleware('auth:sanctum');


Route::prefix("auth")->group(function () {
    Route::post("signup", [SignUpController::class, "register"]);
    Route::post("signin", [SignInController::class, "signInWithEmailAndPassword"]);
    Route::post("signin/otp", [SignInController::class, "signInWithOtp"]);
    Route::post("signin/otp/generate", [SignInController::class, "generateOtp"]);
    Route::post("signout", [SignInController::class, "signOut"])->middleware('auth:sanctum');
});

Route::prefix("admin")->middleware(['auth:sanctum', 'profile.type:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum');
    
    Route::prefix("settings/landing-page")->group(function () {
        Route::get("/", [\App\Http\Controllers\Api\Panel\LandingPageSettingsController::class, "index"]);
        Route::put("/", [\App\Http\Controllers\Api\Panel\LandingPageSettingsController::class, "update"]);
        Route::post("/extract-colors", [\App\Http\Controllers\Api\Panel\LandingPageSettingsController::class, "extractColors"]);
    });

    Route::prefix("profiles")->group(function () {
        Route::post("/", [ProfileController::class, "store"]);
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, "index"]);
        Route::get("/{id}", [ProductController::class, "show"]);
        Route::put("/{id}", [ProductController::class, "update"]);
        Route::delete("/{id}", [ProductController::class, "destroy"]);
        Route::post("/single", [ProductController::class, "storeSingle"]);
        Route::post("/multiple", [ProductController::class, "storeMultiple"]);
        Route::post("/all", [ProductController::class, "storeAll"]);
    });

    Route::prefix("stores")->group(function () {
        Route::get("/", [StoresController::class, "index"]);
        Route::post("/", [StoresController::class, "store"]);
        Route::put("/{id}", [StoresController::class, "update"]);
        Route::get("/{id}", [StoresController::class, "show"]);
        Route::delete("/{id}", [StoresController::class, "destroy"]);
    });

    Route::prefix("branches")->group(function () {
        Route::get("/", [BranchesController::class, "index"]);
        Route::post("/", [BranchesController::class, "store"]);
        Route::put("/{id}", [BranchesController::class, "update"]);
        Route::get("/{id}", [BranchesController::class, "show"]);
        Route::delete("/{id}", [BranchesController::class, "destroy"]);
    });

    Route::prefix("warehouses")->group(function () {
        Route::get("/", [WarehousesController::class, "index"]);
        Route::post("/", [WarehousesController::class, "store"]);
        Route::get("/types", [WarehousesController::class, "getTypes"]);
        Route::get("/{id}", [WarehousesController::class, "show"]);
        Route::put("/{id}", [WarehousesController::class, "update"]);
        Route::delete("/{id}", [WarehousesController::class, "destroy"]);
    });

    Route::get("/inventory/locations", [StorageLocationController::class, "index"]);
    Route::post("/inventory/locations", [StorageLocationController::class, "store"]);

    Route::prefix("categories")->group(function () {
        Route::get("/", [CategoryController::class, "index"]);
        Route::post("/", [CategoryController::class, "store"]);
        Route::get("/{category}", [CategoryController::class, "show"]);
        Route::put("/{category}", [CategoryController::class, "update"]);
        Route::delete("/{category}", [CategoryController::class, "destroy"]);
    });

    Route::prefix("inventory/stock")->group(function () {
        Route::get("/", [StockController::class, "index"]);
        Route::patch("/{id}/quantity", [StockController::class, "updateQuantity"]);
        Route::post("/adjust", [StockController::class, "adjust"]);
        Route::post("/transfer", [StockController::class, "transfer"]);
        Route::get("/movements", [StockController::class, "movements"]);
        Route::post("/movements/{id}/confirm", [StockController::class, "confirmMovement"]);
        Route::post("/movements/{id}/cancel", [StockController::class, "cancelMovement"]);
        Route::get("/adjustments", [StockController::class, "adjustments"]);
        Route::post("/adjustments/{id}/confirm", [StockController::class, "confirmAdjustment"]);
        Route::get("/transfers", [StockController::class, "transfers"]);
        Route::post("/transfer/{id}/confirm", [StockController::class, "confirmTransfer"]);
        Route::post("/transfer/{id}/cancel", [StockController::class, "cancelTransfer"]);
    });

    Route::get("/activity-logs", [ActivityLogController::class, "index"]);
});

Route::prefix("shop")->group(function () {
    Route::get("landing-page", [\App\Http\Controllers\Api\Shop\LandingPageController::class, "index"]);
    Route::get("catalog/products", [\App\Http\Controllers\Api\Shop\CatalogController::class, "index"]);
    Route::get("catalog/products/{id}", [\App\Http\Controllers\Api\Shop\CatalogController::class, "show"]);
    Route::get("categories", [\App\Http\Controllers\Api\Shop\CategoryController::class, "index"]);
});
