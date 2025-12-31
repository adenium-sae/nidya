<?php

use App\Http\Controllers\Admin\Auth\SignInController as AdminSignInController;
use App\Http\Controllers\Admin\Auth\SignUpController as AdminSignUpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("auth")->group(function () {
    Route::prefix("admin")->group(function () {
        Route::post("signup", [AdminSignUpController::class, "register"]);
        Route::post("signin", [AdminSignInController::class, "login"]);
    });
});