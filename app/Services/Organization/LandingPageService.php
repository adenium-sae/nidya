<?php

namespace App\Services\Organization;

use App\Actions\Organization\Settings\UpdateLandingPageSettingsAction;
use App\Models\LandingPageSetting;
use Illuminate\Http\UploadedFile;

class LandingPageService
{
    public function __construct(
        protected UpdateLandingPageSettingsAction $updateAction,
    ) {}

    // --- Queries ---

    public function getSettings(): ?LandingPageSetting
    {
        return LandingPageSetting::first();
    }

    public function getBranding(): array
    {
        $settings = $this->getSettings();

        return $settings ? [
            'display_name'    => $settings->display_name ?: 'NidyaShop',
            'logo_url'        => $settings->logo_url,
            'icon_url'        => $settings->icon_url,
            'primary_color'   => $settings->primary_color ?: '#3B82F6',
            'secondary_color' => $settings->secondary_color ?: '#6B7280',
            'accent_color'    => $settings->accent_color ?: '#F59E0B',
        ] : null;
    }

    public function getPublicData(): array
    {
        $settings = $this->getSettings();

        return [
            'settings' => $settings ? [
                'hero_title'     => $settings->hero_title,
                'hero_subtitle'  => $settings->hero_subtitle,
                'hero_image_url' => $settings->hero_image_url,
                'about_us_text'  => $settings->about_us_text,
                'contact_email'  => $settings->contact_email,
                'contact_phone'  => $settings->contact_phone,
            ] : (object) [],
            'branding' => $this->getBranding(),
        ];
    }

    // --- Mutations (delegated to Action) ---

    public function update(array $data, ?UploadedFile $heroImage, ?UploadedFile $logo, ?UploadedFile $icon): LandingPageSetting
    {
        return ($this->updateAction)($data, $heroImage, $logo, $icon);
    }

    // --- Color extraction ---

    /**
     * Extract dominant colors from an uploaded image using k-means clustering.
     *
     * @return array{colors: array<array{hex: string, role: string}>}
     */
    public function extractColorsFromImage(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return $this->defaultPalette();
        }

        $mime  = $imageInfo['mime'];
        $image = match ($mime) {
            'image/png'  => imagecreatefrompng($path),
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => null,
        };

        if (!$image) {
            return $this->defaultPalette();
        }

        $pixelColors = $this->samplePixels($image);
        imagedestroy($image);

        if (count($pixelColors) < 3) {
            return $this->defaultPalette();
        }

        $clusters = $this->kMeans($pixelColors, 5, 15);
        usort($clusters, fn($a, $b) => $b['count'] <=> $a['count']);

        $palette = $this->buildDistinctPalette($clusters);
        $result  = $this->assignColorRoles($palette);

        return ['colors' => $result];
    }

    // --- Private helpers ---

    private function defaultPalette(): array
    {
        return [
            'colors' => [
                ['hex' => '#3B82F6', 'role' => 'primary'],
                ['hex' => '#6B7280', 'role' => 'secondary'],
                ['hex' => '#F59E0B', 'role' => 'accent'],
            ],
        ];
    }

    private function samplePixels($image): array
    {
        $width  = imagesx($image);
        $height = imagesy($image);
        $pixels = [];
        $step   = max(1, intval(min($width, $height) / 40));

        for ($x = 0; $x < $width; $x += $step) {
            for ($y = 0; $y < $height; $y += $step) {
                $rgb = imagecolorat($image, $x, $y);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8) & 0xFF;
                $b   = $rgb & 0xFF;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $lum = ($max + $min) / 2 / 255;
                $sat = $max === 0 ? 0 : ($max - $min) / $max;

                // Skip near-white, near-black, and very desaturated pixels
                if ($lum < 0.08 || $lum > 0.92) continue;
                if ($sat < 0.10) continue;

                $pixels[] = [$r, $g, $b];
            }
        }

        return $pixels;
    }

    private function kMeans(array $pixels, int $k, int $maxIter): array
    {
        $indices = array_rand($pixels, min($k, count($pixels)));
        if (!is_array($indices)) {
            $indices = [$indices];
        }

        $centroids = array_map(fn($i) => $pixels[$i], $indices);

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $clusters = array_fill(0, count($centroids), ['r' => 0, 'g' => 0, 'b' => 0, 'count' => 0]);

            foreach ($pixels as $px) {
                $minDist = PHP_FLOAT_MAX;
                $closest = 0;
                foreach ($centroids as $ci => $c) {
                    $dist = ($px[0] - $c[0]) ** 2 + ($px[1] - $c[1]) ** 2 + ($px[2] - $c[2]) ** 2;
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $closest = $ci;
                    }
                }
                $clusters[$closest]['r'] += $px[0];
                $clusters[$closest]['g'] += $px[1];
                $clusters[$closest]['b'] += $px[2];
                $clusters[$closest]['count']++;
            }

            foreach ($clusters as $ci => $cl) {
                if ($cl['count'] > 0) {
                    $centroids[$ci] = [
                        $cl['r'] / $cl['count'],
                        $cl['g'] / $cl['count'],
                        $cl['b'] / $cl['count'],
                    ];
                }
            }
        }

        // Final pass to get counts
        $results = array_fill(0, count($centroids), ['r' => 0, 'g' => 0, 'b' => 0, 'count' => 0]);
        foreach ($pixels as $px) {
            $minDist = PHP_FLOAT_MAX;
            $closest = 0;
            foreach ($centroids as $ci => $c) {
                $dist = ($px[0] - $c[0]) ** 2 + ($px[1] - $c[1]) ** 2 + ($px[2] - $c[2]) ** 2;
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $closest = $ci;
                }
            }
            $results[$closest]['count']++;
        }

        foreach ($centroids as $ci => $c) {
            $results[$ci]['r'] = $c[0];
            $results[$ci]['g'] = $c[1];
            $results[$ci]['b'] = $c[2];
        }

        return array_filter($results, fn($c) => $c['count'] > 0);
    }

    private function buildDistinctPalette(array $clusters): array
    {
        $palette = [];

        foreach ($clusters as $c) {
            $hex = sprintf('#%02X%02X%02X', (int) $c['r'], (int) $c['g'], (int) $c['b']);
            $tooClose = false;
            foreach ($palette as $existing) {
                if ($this->colorDistance($hex, $existing['hex']) < 50) {
                    $tooClose = true;
                    break;
                }
            }
            if (!$tooClose) {
                $palette[] = ['hex' => $hex];
            }
        }

        while (count($palette) < 3) {
            $palette[] = ['hex' => '#6B7280'];
        }

        return array_slice($palette, 0, 5);
    }

    private function assignColorRoles(array $palette): array
    {
        $withMeta = array_map(function ($c) {
            $cleaned = ltrim($c['hex'], '#');
            $r   = hexdec(substr($cleaned, 0, 2));
            $g   = hexdec(substr($cleaned, 2, 2));
            $b   = hexdec(substr($cleaned, 4, 2));
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $sat = $max === 0 ? 0 : ($max - $min) / $max;

            return array_merge($c, ['sat' => $sat]);
        }, $palette);

        usort($withMeta, fn($a, $b) => $b['sat'] <=> $a['sat']);

        $roles  = ['primary', 'secondary', 'accent'];
        $result = [];
        for ($i = 0; $i < min(3, count($withMeta)); $i++) {
            $result[] = ['hex' => $withMeta[$i]['hex'], 'role' => $roles[$i]];
        }

        return $result;
    }

    private function colorDistance(string $hex1, string $hex2): float
    {
        $c1 = ltrim($hex1, '#');
        $c2 = ltrim($hex2, '#');

        $r1 = hexdec(substr($c1, 0, 2));
        $g1 = hexdec(substr($c1, 2, 2));
        $b1 = hexdec(substr($c1, 4, 2));

        $r2 = hexdec(substr($c2, 0, 2));
        $g2 = hexdec(substr($c2, 2, 2));
        $b2 = hexdec(substr($c2, 4, 2));

        return sqrt(($r1 - $r2) ** 2 + ($g1 - $g2) ** 2 + ($b1 - $b2) ** 2);
    }
}
