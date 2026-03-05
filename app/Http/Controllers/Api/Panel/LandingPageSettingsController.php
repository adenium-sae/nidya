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
            'hero_image' => 'nullable|image|max:2048',
            'about_us_text' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        $settings = \App\Models\LandingPageSetting::first() ?? new \App\Models\LandingPageSetting();
        $settings->fill(collect($validated)->except('hero_image')->toArray());

        if ($request->hasFile('hero_image')) {
            $path = $request->file('hero_image')->store('settings', 'public');
            $settings->hero_image_url = '/storage/' . $path;
        }

        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }
}
