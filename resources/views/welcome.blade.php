<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    @php
        $siteName = (is_array($branding) && !empty($branding['display_name'])) ? $branding['display_name'] : 'Nidya';
        $iconUrl = is_array($branding) ? ($branding['icon_url'] ?? null) : null;
        $logoUrl = is_array($branding) ? ($branding['logo_url'] ?? null) : null;
    @endphp

    <title>{{ $siteName }}</title>
    
    <!-- Open Graph / Meta Tags -->
    <meta property="og:title" content="{{ $siteName }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta name="twitter:title" content="{{ $siteName }}">
    @if($iconUrl)
        <link rel="icon" href="{{ $iconUrl }}">
    @endif
    @if($logoUrl)
        <meta property="og:image" content="{{ $logoUrl }}">
        <meta name="twitter:image" content="{{ $logoUrl }}">
    @elseif($iconUrl)
        <meta property="og:image" content="{{ $iconUrl }}">
        <meta name="twitter:image" content="{{ $iconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>