<?php

use App\Http\Controllers\Admin\Auth\SignInController as AdminSignInController;
use App\Http\Controllers\Admin\Auth\SignUpController as AdminSignUpController;
use App\Http\Controllers\Admin\Profiles\ProfileController as AdminProfileController;
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
    });
});

Route::prefix("admin")->middleware(['auth:sanctum', 'profile.type:admin'])->group(function () {
    Route::prefix("profiles")->group(function () {
        Route::post("/", [AdminProfileController::class, "store"]);
    });
});