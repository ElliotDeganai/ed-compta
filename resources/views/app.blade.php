<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $siteName }}</title>
    <meta name="description" content="{{ $siteTagline }}">
    <meta name="theme-color" content="#042C53">

    {{-- Onglet du navigateur. Le SVG est servi aux navigateurs modernes, les
         PNG couvrent les anciens et l'ecran d'accueil iOS. --}}
    <link rel="icon" href="{{ $branding['favicon'] }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('favicon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Partage sur les reseaux sociaux. og:image doit etre une URL absolue
         en PNG ou JPG : les plateformes ne lisent pas le SVG. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $siteName }}">
    <meta property="og:description" content="{{ $siteTagline }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $branding['og_image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="fr_CH">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $siteName }}">
    <meta name="twitter:description" content="{{ $siteTagline }}">
    <meta name="twitter:image" content="{{ $branding['og_image'] }}">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
