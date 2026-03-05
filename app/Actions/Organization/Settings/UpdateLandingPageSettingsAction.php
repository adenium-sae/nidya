<?php

namespace App\Actions\Organization\Settings;

use App\Models\LandingPageSetting;
use Illuminate\Http\UploadedFile;

class UpdateLandingPageSettingsAction
{
    public function __invoke(array $data, ?UploadedFile $heroImage, ?UploadedFile $logo, ?UploadedFile $icon): LandingPageSetting
    {
        $settings = LandingPageSetting::first() ?? new LandingPageSetting();
        $settings->fill($data);
        if ($heroImage) {
            $path = $heroImage->store('settings', 'public');
            $settings->hero_image_url = '/storage/' . $path;
        }
        if ($logo) {
            $path = $logo->store('settings/branding', 'public');
            $settings->logo_url = '/storage/' . $path;
        }
        if ($icon) {
            $path = $icon->store('settings/branding', 'public');
            $settings->icon_url = '/storage/' . $path;
        }
        $settings->save();
        return $settings;
    }
}
