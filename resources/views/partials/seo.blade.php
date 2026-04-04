@php $brandName = config('brand.name', 'Zelta'); @endphp

{{-- SEO Meta Tags --}}
<meta name="description" content="{{ $description ?? $brandName . ' — Agentic payments with stablecoin-powered virtual cards. Get your personal card or your AI agent a card to spend anywhere.' }}">
<meta name="keywords" content="{{ $keywords ?? $brandName . ', agentic payments, AI agent card, stablecoin card, virtual card, crypto payments, non-custodial wallet, privacy payments' }}">
<meta name="author" content="{{ $brandName }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">

{{-- Google Search Console Verification --}}
@if(config('brand.google_site_verification'))
<meta name="google-site-verification" content="{{ config('brand.google_site_verification') }}">
@endif

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:url" content="{{ $canonical ?? url()->current() }}">
<meta property="og:title" content="{{ $title ?? $brandName . ' — Agentic Payments' }}">
<meta property="og:description" content="{{ $description ?? $brandName . ' — Agentic payments with stablecoin-powered virtual cards. Get your personal card or your AI agent a card to spend anywhere.' }}">
<meta property="og:image" content="{{ $ogImage ?? asset('images/og-default.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="{{ $brandName }}">
<meta property="og:locale" content="en_US">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $canonical ?? url()->current() }}">
<meta name="twitter:title" content="{{ $title ?? $brandName . ' — Agentic Payments' }}">
<meta name="twitter:description" content="{{ $description ?? $brandName . ' — Agentic payments with stablecoin-powered virtual cards. Get your personal card or your AI agent a card to spend anywhere.' }}">
<meta name="twitter:image" content="{{ $twitterImage ?? asset('images/og-twitter.png') }}">
<meta name="twitter:domain" content="{{ parse_url(config('app.url'), PHP_URL_HOST) }}">
@if(config('brand.twitter_handle'))
<meta name="twitter:site" content="{{ config('brand.twitter_handle') }}">
<meta name="twitter:creator" content="{{ config('brand.twitter_handle') }}">
@endif

{{-- Additional SEO Tags --}}
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $brandName }}">
<meta name="application-name" content="{{ $brandName }}">

{{-- Schema.org JSON-LD --}}
@if(isset($schema))
{!! $schema !!}
@endif