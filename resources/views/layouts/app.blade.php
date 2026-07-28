@php($isRtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pink Caravan')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php($fontHref = 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap')
    {{-- Load fonts non-render-blocking; falls back to system-ui if the CDN is slow/unavailable (clinics with poor connectivity). --}}
    <link href="{{ $fontHref }}" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="{{ $fontHref }}" rel="stylesheet"></noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="pcx pc-scroll" dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
     style="min-height:100vh;color:#2A2230;background:#F4EEF1;">
    @yield('content')
</div>
</body>
</html>
