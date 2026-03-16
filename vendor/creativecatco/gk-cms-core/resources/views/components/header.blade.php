{{-- Fallback header component (used when no header page_type exists in DB) --}}
@php
    $siteName = \CreativeCatCo\GkCmsCore\Models\Setting::get('site_name',
                \CreativeCatCo\GkCmsCore\Models\Setting::get('company_name',
                config('cms.site_name', 'My Website')));
    $siteTagline = \CreativeCatCo\GkCmsCore\Models\Setting::get('tagline',
                   \CreativeCatCo\GkCmsCore\Models\Setting::get('company_tagline', ''));
    $logo = \CreativeCatCo\GkCmsCore\Models\Setting::get('logo');
    $showTagline = filter_var(\CreativeCatCo\GkCmsCore\Models\Setting::get('show_tagline_header', false), FILTER_VALIDATE_BOOLEAN);

    // Build the logo URL
    $logoUrl = '';
    if ($logo) {
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            $logoUrl = $logo;
        } else {
            $logoUrl = asset('storage/' . $logo);
        }
    }
@endphp

<header class="shadow-sm sticky top-0 z-50" style="background-color: var(--header-bg, #15171e)">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    @if($logoUrl)
                        {{-- Logo present: show image, hide text title --}}
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto">
                    @else
                        {{-- No logo: show text title --}}
                        <span class="text-xl font-bold text-white">{{ $siteName }}</span>
                    @endif

                    {{-- Tagline: only shown when explicitly enabled in settings --}}
                    @if($showTagline && $siteTagline)
                        <span class="hidden sm:inline-block text-xs text-gray-400 border-l border-gray-600 pl-3 ml-1">{{ $siteTagline }}</span>
                    @endif
                </a>
            </div>

            <div class="hidden md:flex md:items-center md:space-x-6">
                @php
                    $navPages = \CreativeCatCo\GkCmsCore\Models\Page::where('status', 'published')
                        ->whereIn('page_type', ['page', null])
                        ->where('show_in_nav', true)
                        ->orderBy('sort_order')
                        ->get();
                @endphp
                @foreach($navPages as $navPage)
                    <a href="{{ url($navPage->slug) }}" class="text-sm font-medium text-gray-200 hover:text-white transition-colors">
                        {{ $navPage->title }}
                    </a>
                @endforeach
                <a href="{{ url('blog') }}" class="text-sm font-medium text-gray-200 hover:text-white transition-colors">
                    Blog
                </a>
            </div>
        </div>
    </nav>
</header>
