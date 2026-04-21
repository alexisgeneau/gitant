<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Gitant') }}</title>

        {{-- Default SEO meta (overridden per-page by Inertia <Head>) --}}
        <meta name="description" content="Gitant — bounty platform for open source. Fund or resolve GitHub and GitLab issues with Stripe-secured bounties.">
        <meta name="theme-color" content="#4f46e5">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Gitant">
        <meta property="og:title" content="Gitant — open source bounties">
        <meta property="og:description" content="Fund or resolve GitHub and GitLab issues with Stripe-secured bounties.">
        <meta property="og:image" content="{{ asset('images/og-default.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Gitant — open source bounties">
        <meta name="twitter:description" content="Fund or resolve GitHub and GitLab issues with Stripe-secured bounties.">
        <meta name="twitter:image" content="{{ asset('images/og-default.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
        @inertia
    </body>
</html>
