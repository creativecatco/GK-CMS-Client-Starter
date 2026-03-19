<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Page Title --}}
    <title>{{ $seo['title'] ?? config('cms.site_name', 'My Website') }}</title>

    {{-- Meta Description --}}
    @if(!empty($seo['meta_description']))
        <meta name="description" content="{{ $seo['meta_description'] }}">
    @endif

    {{-- Canonical URL --}}
    @if(!empty($seo['canonical_url']))
        <link rel="canonical" href="{{ $seo['canonical_url'] }}">
    @endif

    {{-- Open Graph Tags --}}
    @if(!empty($seo['og']))
        <meta property="og:type" content="{{ $seo['og']['type'] ?? 'website' }}">
        <meta property="og:title" content="{{ $seo['og']['title'] ?? '' }}">
        <meta property="og:description" content="{{ $seo['og']['description'] ?? '' }}">
        <meta property="og:url" content="{{ $seo['og']['url'] ?? '' }}">
        <meta property="og:site_name" content="{{ $seo['og']['site_name'] ?? '' }}">
        @if(!empty($seo['og']['image']))
            <meta property="og:image" content="{{ $seo['og']['image'] }}">
        @endif
    @endif

    {{-- Twitter Card Tags --}}
    @if(!empty($seo['twitter']))
        <meta name="twitter:card" content="{{ $seo['twitter']['card'] ?? 'summary' }}">
        <meta name="twitter:title" content="{{ $seo['twitter']['title'] ?? '' }}">
        <meta name="twitter:description" content="{{ $seo['twitter']['description'] ?? '' }}">
        @if(!empty($seo['twitter']['image']))
            <meta name="twitter:image" content="{{ $seo['twitter']['image'] }}">
        @endif
    @endif

    {{-- JSON-LD Structured Data --}}
    @if(!empty($seo['json_ld']))
        @foreach($seo['json_ld'] as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
            </script>
        @endforeach
    @endif

    {{-- Favicon --}}
    @php
        $favicon = \CreativeCatCo\GkCmsCore\Models\Setting::get('favicon');
    @endphp
    @if($favicon)
        <link rel="icon" href="{{ asset('storage/' . $favicon) }}">
    @else
        {{-- Default GKeys favicon --}}
        <link rel="icon" href="{{ asset('vendor/cms-core/img/gkeys-icon.png') }}" type="image/png">
    @endif

    {{-- Google Analytics --}}
    @php
        $gaId = \CreativeCatCo\GkCmsCore\Models\Setting::get('google_analytics_id');
    @endphp
    @if($gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif

    {{-- GoHighLevel Tracking --}}
    @php
        $ghlTrackingId = \CreativeCatCo\GkCmsCore\Models\Setting::get('ghl_tracking_id');
        $ghlTrackingDomain = \CreativeCatCo\GkCmsCore\Models\Setting::get('ghl_tracking_domain');
    @endphp
    @if($ghlTrackingId)
        <script src="{{ $ghlTrackingDomain ? rtrim($ghlTrackingDomain, '/') : 'https://link.msgsndr.com' }}/js/external-tracking.js" data-tracking-id="{{ $ghlTrackingId }}"></script>
    @endif

    {{-- Custom Head Code (from Settings) --}}
    @php
        $customHeadCode = \CreativeCatCo\GkCmsCore\Models\Setting::get('custom_head_code');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif

    {{-- Custom Font Embed Code (must be before Google Fonts so custom fonts load first) --}}
    @php
        $customFontEmbed = \CreativeCatCo\GkCmsCore\Models\Setting::get('custom_font_embed', '');
    @endphp
    @if($customFontEmbed)
        {!! $customFontEmbed !!}
    @endif

    {{-- Theme Variables (from Settings) --}}
    @php
        $themePrimary = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_primary_color', '#cfff2e');
        $themeSecondary = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_secondary_color', '#293726');
        $themeAccent = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_accent_color', '#3b82f6');
        $themeText = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_text_color', '#1a1a2e');
        $themeBg = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_bg_color', '#ffffff');
        $themeHeaderBg = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_header_bg', '#15171e');
        $themeFooterBg = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_footer_bg', '#15171e');
        $fontHeading = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_font_heading', 'Inter');
        $fontBody = \CreativeCatCo\GkCmsCore\Models\Setting::get('theme_font_body', 'Inter');
    @endphp

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontHeading) }}:wght@400;500;600;700;800&family={{ urlencode($fontBody) }}:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS --}}
    {{-- Use Play CDN for JIT support (bg-black/20, arbitrary values, etc.) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Suppress the "should not be used in production" console warning
        if (typeof tailwind !== 'undefined') {
            tailwind.config = {
                corePlugins: { preflight: true }
            };
        }
    </script>

    {{-- CSS Variables --}}
    <style>
        :root {
            --color-primary: {{ $themePrimary }};
            --color-secondary: {{ $themeSecondary }};
            --color-accent: {{ $themeAccent }};
            --color-text: {{ $themeText }};
            --color-bg: {{ $themeBg }};
            --header-bg: {{ $themeHeaderBg }};
            --footer-bg: {{ $themeFooterBg }};
            --font-heading: '{{ $fontHeading }}', system-ui, -apple-system, sans-serif;
            --font-body: '{{ $fontBody }}', system-ui, -apple-system, sans-serif;
        }
        body {
            font-family: var(--font-body);
            color: var(--color-text);
            background-color: var(--color-bg);
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
        }
        .prose { max-width: 65ch; }
        .prose h1 { font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2; }
        .prose h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .prose p { margin-bottom: 1rem; line-height: 1.75; }
        .prose ul, .prose ol { margin-bottom: 1rem; padding-left: 1.5rem; }
        .prose li { margin-bottom: 0.25rem; }
        .prose a { color: var(--color-accent); text-decoration: underline; }
        .prose img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1.5rem 0; }
        .prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; font-style: italic; }
        [x-cloak] { display: none !important; }
    </style>

    {{-- Global CSS (from Settings) --}}
    @php
        $globalCss = \CreativeCatCo\GkCmsCore\Models\Setting::get('global_css', '');
    @endphp
    @if($globalCss)
        <style id="gk-global-css">{!! $globalCss !!}</style>
    @endif

    {{-- Header/Footer component CSS --}}
    @php
        $__headerPageCss = \CreativeCatCo\GkCmsCore\Models\Page::where('page_type', 'header')
            ->where('status', 'published')
            ->whereNotNull('custom_css')
            ->value('custom_css');
        $__footerPageCss = \CreativeCatCo\GkCmsCore\Models\Page::where('page_type', 'footer')
            ->where('status', 'published')
            ->whereNotNull('custom_css')
            ->value('custom_css');
    @endphp
    @if(!empty($__headerPageCss))
        <style id="gk-header-css">{!! $__headerPageCss !!}</style>
    @endif
    @if(!empty($__footerPageCss))
        <style id="gk-footer-css">{!! $__footerPageCss !!}</style>
    @endif

    {{-- Page-specific CSS --}}
    @if(!empty($page) && !empty($page->custom_css))
        <style id="gk-page-css">{!! $page->custom_css !!}</style>
    @endif

    {{-- Alpine.js for interactive blocks (FAQ accordion, form states, etc.) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="antialiased">
    {{-- Skip to content for accessibility --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white focus:text-black">
        Skip to content
    </a>

    {{-- Header: Load from database page_type='header' or fall back to component --}}
    @hasSection('header')
        @yield('header')
    @else
        @php
            // Find the best matching header for this page
            $__allHeaders = \CreativeCatCo\GkCmsCore\Models\Page::where('page_type', 'header')
                ->where('status', 'published')
                ->get();
            $headerPage = null;
            $__currentPage = $page ?? null;
            $__currentPageType = $__currentPage->page_type ?? 'page';
            $__currentPageId = $__currentPage->id ?? null;

            // Priority: specific_pages > specific_types > all
            foreach ($__allHeaders as $__h) {
                $scope = $__h->display_scope ?? 'all';
                $displayOn = $__h->display_on ?? [];

                if ($scope === 'specific_pages' && $__currentPageId && in_array($__currentPageId, $displayOn)) {
                    $headerPage = $__h;
                    break; // Exact page match wins
                }
            }
            if (!$headerPage) {
                foreach ($__allHeaders as $__h) {
                    $scope = $__h->display_scope ?? 'all';
                    $displayOn = $__h->display_on ?? [];

                    if ($scope === 'specific_types' && in_array($__currentPageType, $displayOn)) {
                        $headerPage = $__h;
                        break; // Page type match is second priority
                    }
                }
            }
            if (!$headerPage) {
                // Fall back to the 'all' scope header (or any header if no scope is set)
                $headerPage = $__allHeaders->first(fn($__h) => ($__h->display_scope ?? 'all') === 'all');
            }
            if (!$headerPage) {
                // Ultimate fallback: any published header
                $headerPage = $__allHeaders->first();
            }
        @endphp
        @if($headerPage && $headerPage->template === 'custom' && !empty($headerPage->custom_template))
            {{-- Custom template: render the stored HTML/Blade directly with error protection --}}
            @php
                try {
                    $__headerSettings = method_exists(\CreativeCatCo\GkCmsCore\Models\Setting::class, 'allCached')
                        ? \CreativeCatCo\GkCmsCore\Models\Setting::allCached()
                        : (method_exists(\CreativeCatCo\GkCmsCore\Models\Setting::class, 'getGroup')
                            ? \CreativeCatCo\GkCmsCore\Models\Setting::getGroup('general')
                            : []);
                    echo \Illuminate\Support\Facades\Blade::render($headerPage->custom_template, [
                        'page' => $headerPage,
                        'fields' => $headerPage->fields ?? [],
                        'settings' => $__headerSettings,
                    ]);
                } catch (\Throwable $e) {
                    if (config('app.debug')) {
                        echo '<div style="background:#fee2e2;border:2px solid #ef4444;color:#991b1b;padding:16px;margin:8px;border-radius:8px;font-family:monospace;font-size:13px;"><strong>Header Template Error:</strong> ' . e($e->getMessage()) . '</div>';
                    }
                    \Illuminate\Support\Facades\Log::error('Header custom template render error: ' . $e->getMessage());
                }
            @endphp
        @elseif($headerPage && $headerPage->template && $headerPage->template !== 'custom')
            @include('cms-core::pages.' . $headerPage->template, [
                'page' => $headerPage,
                'fields' => $headerPage->fields ?? [],
                'seo' => [],
            ])
        @else
            @include('cms-core::components.header')
        @endif
    @endif

    {{-- Main Content --}}
    <main id="main-content">
        @yield('content')
    </main>

    {{-- Footer: Load from database page_type='footer' or fall back to component --}}
    @hasSection('footer')
        @yield('footer')
    @else
        @php
            // Find the best matching footer for this page
            $__allFooters = \CreativeCatCo\GkCmsCore\Models\Page::where('page_type', 'footer')
                ->where('status', 'published')
                ->get();
            $footerPage = null;
            $__currentPage = $__currentPage ?? ($page ?? null);
            $__currentPageType = $__currentPage->page_type ?? 'page';
            $__currentPageId = $__currentPage->id ?? null;

            // Priority: specific_pages > specific_types > all
            foreach ($__allFooters as $__f) {
                $scope = $__f->display_scope ?? 'all';
                $displayOn = $__f->display_on ?? [];

                if ($scope === 'specific_pages' && $__currentPageId && in_array($__currentPageId, $displayOn)) {
                    $footerPage = $__f;
                    break;
                }
            }
            if (!$footerPage) {
                foreach ($__allFooters as $__f) {
                    $scope = $__f->display_scope ?? 'all';
                    $displayOn = $__f->display_on ?? [];

                    if ($scope === 'specific_types' && in_array($__currentPageType, $displayOn)) {
                        $footerPage = $__f;
                        break;
                    }
                }
            }
            if (!$footerPage) {
                $footerPage = $__allFooters->first(fn($__f) => ($__f->display_scope ?? 'all') === 'all');
            }
            if (!$footerPage) {
                $footerPage = $__allFooters->first();
            }
        @endphp
        @if($footerPage && $footerPage->template === 'custom' && !empty($footerPage->custom_template))
            {{-- Custom template: render the stored HTML/Blade directly with error protection --}}
            @php
                try {
                    $__footerSettings = method_exists(\CreativeCatCo\GkCmsCore\Models\Setting::class, 'allCached')
                        ? \CreativeCatCo\GkCmsCore\Models\Setting::allCached()
                        : (method_exists(\CreativeCatCo\GkCmsCore\Models\Setting::class, 'getGroup')
                            ? \CreativeCatCo\GkCmsCore\Models\Setting::getGroup('general')
                            : []);
                    echo \Illuminate\Support\Facades\Blade::render($footerPage->custom_template, [
                        'page' => $footerPage,
                        'fields' => $footerPage->fields ?? [],
                        'settings' => $__footerSettings,
                    ]);
                } catch (\Throwable $e) {
                    if (config('app.debug')) {
                        echo '<div style="background:#fee2e2;border:2px solid #ef4444;color:#991b1b;padding:16px;margin:8px;border-radius:8px;font-family:monospace;font-size:13px;"><strong>Footer Template Error:</strong> ' . e($e->getMessage()) . '</div>';
                    }
                    \Illuminate\Support\Facades\Log::error('Footer custom template render error: ' . $e->getMessage());
                }
            @endphp
        @elseif($footerPage && $footerPage->template && $footerPage->template !== 'custom')
            @include('cms-core::pages.' . $footerPage->template, [
                'page' => $footerPage,
                'fields' => $footerPage->fields ?? [],
                'seo' => [],
            ])
        @else
            @include('cms-core::components.footer')
        @endif
    @endif

    {{-- Custom Body Code (from Settings) --}}
    @php
        $customBodyCode = \CreativeCatCo\GkCmsCore\Models\Setting::get('custom_body_code');
    @endphp
    @if($customBodyCode)
        {!! $customBodyCode !!}
    @endif

    {{-- Render background overlays and gradients from saved fields --}}
    @if(!empty($fields))
    <script>
        (function() {
            const fields = @json($fields ?? []);
            document.querySelectorAll('[data-section-bg]').forEach(function(el) {
                const key = el.dataset.sectionBg;
                const bgData = fields[key];
                if (!bgData || typeof bgData !== 'object') return;

                // Apply saved background color/gradient
                if (bgData.colorType === 'gradient' && bgData.gradient) {
                    el.style.background = bgData.gradient;
                } else if (bgData.color) {
                    el.style.backgroundColor = bgData.color;
                }

                // Apply saved background image
                if (bgData.image) {
                    el.style.backgroundImage = 'url(/storage/' + bgData.image + ')';
                    el.style.backgroundSize = bgData.mode === 'contain' ? 'contain' : (bgData.mode === 'repeat' ? 'auto' : 'cover');
                    el.style.backgroundRepeat = bgData.mode === 'repeat' ? 'repeat' : 'no-repeat';
                    el.style.backgroundPosition = 'center';
                    if (bgData.mode === 'fixed') el.style.backgroundAttachment = 'fixed';
                }

                // Apply saved overlay
                if (bgData.overlay && bgData.overlay.type && bgData.overlay.type !== 'none') {
                    var overlayEl = el.querySelector('.gk-section-overlay');
                    if (!overlayEl) {
                        overlayEl = document.createElement('div');
                        overlayEl.className = 'gk-section-overlay';
                        overlayEl.style.cssText = 'position:absolute;inset:0;pointer-events:none;z-index:0;';
                        el.insertBefore(overlayEl, el.firstChild);
                        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
                    }
                    if (bgData.overlay.type === 'solid' && bgData.overlay.solid) {
                        overlayEl.style.background = bgData.overlay.solid;
                    } else if (bgData.overlay.type === 'gradient' && bgData.overlay.gradient) {
                        var g = bgData.overlay.gradient;
                        if (g.type === 'radial') {
                            overlayEl.style.background = 'radial-gradient(circle, ' + g.color1 + ', ' + g.color2 + ')';
                        } else {
                            overlayEl.style.background = 'linear-gradient(' + (g.angle || 180) + 'deg, ' + g.color1 + ', ' + g.color2 + ')';
                        }
                    }
                    // Ensure children are above overlay
                    Array.from(el.children).forEach(function(child) {
                        if (!child.classList.contains('gk-section-overlay')) {
                            if (getComputedStyle(child).position === 'static') child.style.position = 'relative';
                            if (!child.style.zIndex) child.style.zIndex = '1';
                        }
                    });
                }
            });
        })();
    </script>
    @endif

     {{-- Inline Editor (only for authenticated admins/editors) --}}
    @include('cms-core::components.inline-editor')

    {{-- Beta Version Banner --}}
    @php
        $cmsVersion = '0.7.1';
        try {
            $pkgComposer = dirname((new ReflectionClass(\CreativeCatCo\GkCmsCore\CmsCoreServiceProvider::class))->getFileName()) . '/../composer.json';
            if (file_exists($pkgComposer)) {
                $pkgData = json_decode(file_get_contents($pkgComposer), true);
                if (!empty($pkgData['version'])) $cmsVersion = $pkgData['version'];
            }
        } catch (\Throwable $e) {}
    @endphp
    <div id="gk-beta-banner" style="position:fixed;bottom:12px;right:12px;z-index:9999;background:rgba(21,23,30,0.85);backdrop-filter:blur(8px);border:1px solid rgba(210,235,0,0.3);border-radius:6px;padding:6px 14px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:11px;color:rgba(246,241,230,0.7);letter-spacing:0.03em;pointer-events:none;">
        <span style="color:#d2eb00;font-weight:700;">BETA</span>
        <span style="margin-left:6px;">v{{ $cmsVersion }}</span>
    </div>

    @stack('scripts')
</body>
</html>
