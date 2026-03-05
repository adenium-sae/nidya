<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index()
    {
        $settings = \App\Models\LandingPageSetting::first();
        return response()->json($settings);
    }
}
