<!DOCTYPE html>
@php
    $isPublicStorefront = ! request()->is('manage') && ! request()->is('manage/*');
    $publicSettings = $isPublicStorefront ? app(\App\Support\PublicSettings::class)->all() : [];
    $analytics = $publicSettings['analytics'] ?? [];
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Drasa') }}</title>

        @if($isPublicStorefront && ($analytics['google_tag_manager_id'] ?? null))
            <!-- Google Tag Manager -->
            <script>
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer',@js($analytics['google_tag_manager_id']));
            </script>
            <!-- End Google Tag Manager -->
        @endif

        @if($isPublicStorefront && ($analytics['google_analytics_id'] ?? null))
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($analytics['google_analytics_id']) }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @js($analytics['google_analytics_id']));
            </script>
        @endif

        @if($isPublicStorefront && ($analytics['meta_pixel_id'] ?? null))
            <!-- Meta Pixel Code -->
            <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', @js($analytics['meta_pixel_id']));
                fbq('track', 'PageView');
            </script>
            <!-- End Meta Pixel Code -->
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if($isPublicStorefront && ($analytics['google_tag_manager_id'] ?? null))
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($analytics['google_tag_manager_id']) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
        @endif

        @if($isPublicStorefront && ($analytics['meta_pixel_id'] ?? null))
            <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ rawurlencode($analytics['meta_pixel_id']) }}&ev=PageView&noscript=1" alt=""></noscript>
        @endif

        @inertia
    </body>
</html>
