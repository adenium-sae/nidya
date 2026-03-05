<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Services\Organization\LandingPageService;

class LandingPageController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService,
    ) {}

    public function index()
    {
        return response()->json($this->landingPageService->getPublicData());
    }
}
