<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    protected $fillable = [
        'display_name',
        'logo_url',
        'icon_url',
        'hero_title',
        'hero_subtitle',
        'hero_image_url',
        'about_us_text',
        'contact_email',
        'contact_phone',
        'primary_color',
        'secondary_color',
        'accent_color',
    ];
}
