<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LandingPageSettingsController extends Controller
{
    public function index()
    {
        $settings = \App\Models\LandingPageSetting::first();
        return response()->json($settings ?: (object)[]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string',
            'hero_image_url' => 'nullable|string',
            'about_us_text' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        $settings = \App\Models\LandingPageSetting::first() ?? new \App\Models\LandingPageSetting();
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }
}
