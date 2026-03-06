<?php

use Illuminate\Support\Facades\Route;
use App\Services\Organization\LandingPageService;

Route::any('/{any?}', function (LandingPageService $service) {
    return view('welcome', [
        'branding' => $service->getBranding()
    ]);
})->where('any', '.*');
