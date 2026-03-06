<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\Organization\LandingPageService;
use Illuminate\Http\Request;

class LandingPageSettingsController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService,
    ) {}

    public function index()
    {
        $settings = $this->landingPageService->getSettings();
        return response()->json($settings ?: (object) []);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'display_name'    => 'nullable|string|max:255',
            'hero_title'      => 'nullable|string|max:255',
            'hero_subtitle'   => 'nullable|string',
            'hero_image'      => 'nullable|image|max:2048',
            'logo'            => 'nullable|image|max:2048',
            'icon'            => 'nullable|image|max:1024',
            'about_us_text'   => 'nullable|string',
            'contact_email'   => 'nullable|email|max:255',
            'contact_phone'   => 'nullable|string|max:50',
            'primary_color'   => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color'    => 'nullable|string|max:7',
        ]);
        $data = collect($validated)->except(['hero_image', 'logo', 'icon'])->toArray();
        $settings = $this->landingPageService->update(
            $data,
            $request->file('hero_image'),
            $request->file('logo'),
            $request->file('icon'),
        );
        return response()->json([
            'message' => 'Settings updated successfully',
            'data'    => $settings,
        ]);
    }

    public function extractColors(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);
        $result = $this->landingPageService->extractColorsFromImage(
            $request->file('image')
        );
        return response()->json($result);
    }
}
