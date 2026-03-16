{{--
    GKeys CMS - Default Header Template
    page_type: header

    Fields:
    - logo_text (text): Site name/logo text (fallback when no logo image)
    - logo_image (image): Logo image
    - show_tagline (toggle): Show tagline in header
    - cta_text (text): CTA button text
    - cta_url (text): CTA button URL
--}}
@php
    $menu = \CreativeCatCo\GkCmsCore\Models\Menu::where('location', 'header')->first();
    $menuItems = $menu ? $menu->items : [];
    $siteName = \CreativeCatCo\GkCmsCore\Models\Setting::get('site_name',
                \CreativeCatCo\GkCmsCore\Models\Setting::get('company_name',
                config('cms.site_name', 'My Website')));
    $siteTagline = \CreativeCatCo\GkCmsCore\Models\Setting::get('tagline',
                   \CreativeCatCo\GkCmsCore\Models\Setting::get('company_tagline', ''));
    $logoSetting = \CreativeCatCo\GkCmsCore\Models\Setting::get('logo', '');
    // show_tagline_header setting controls tagline visibility
    // Defaults to false - tagline is hidden unless explicitly enabled in Settings > General
    $showTagline = filter_var(\CreativeCatCo\GkCmsCore\Models\Setting::get('show_tagline_header', false), FILTER_VALIDATE_BOOLEAN);

    // Determine logo: field override > setting > none
    $logoImage = $fields['logo_image'] ?? $logoSetting;

    // Build the logo URL (handles both storage paths and full URLs)
    $logoUrl = '';
    if ($logoImage) {
        if (str_starts_with($logoImage, 'http://') || str_starts_with($logoImage, 'https://')) {
            $logoUrl = $logoImage;
        } else {
            $logoUrl = asset('storage/' . $logoImage);
        }
    }

    // Helper: resolve a menu item's URL from page_id or label fallback
    // This handles items created via the admin panel (type=page with page_id)
    // as well as items created by the AI tool (direct url field)
    $resolveMenuUrl = function ($item) {
        // If a direct URL is set and it's not just '#', use it
        $url = $item['url'] ?? '';
        if ($url && $url !== '#') {
            return $url;
        }
        // If type is 'page' and page_id is set, resolve from the database
        if (($item['type'] ?? '') === 'page' && !empty($item['page_id'])) {
            $linkedPage = \CreativeCatCo\GkCmsCore\Models\Page::find($item['page_id']);
            if ($linkedPage) {
                return '/' . ltrim($linkedPage->slug, '/');
            }
        }
        // Fallback: try to find a page matching the label (slug-ified)
        $label = $item['label'] ?? '';
        if ($label) {
            $slug = \Illuminate\Support\Str::slug($label);
            $matchedPage = \CreativeCatCo\GkCmsCore\Models\Page::where('slug', $slug)
                ->where('status', 'published')
                ->first();
            if ($matchedPage) {
                return '/' . ltrim($matchedPage->slug, '/');
            }
        }
        return $url ?: '#';
    };
@endphp

<header class="shadow-sm sticky top-0 z-50" style="background-color: var(--header-bg, #15171e)" data-page-id="{{ $page->id ?? '' }}">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Logo / Site Name --}}
            <div class="flex-shrink-0">
                <a href="/" class="flex items-center gap-3">
                    @if($logoUrl)
                        {{-- Logo image present: show logo, hide site title --}}
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto" data-field="logo_image" data-field-type="image">
                    @else
                        {{-- No logo: show site name as text --}}
                        <span class="text-xl font-bold text-white" data-field="logo_text" data-field-type="text">{{ (!empty($fields['logo_text']) && $fields['logo_text'] !== 'My Website') ? $fields['logo_text'] : $siteName }}</span>
                    @endif

                    {{-- Tagline: only shown when explicitly enabled in Settings > General --}}
                    @if($showTagline && $siteTagline)
                        <span class="hidden sm:inline-block text-xs text-gray-400 border-l border-gray-600 pl-3 ml-1">{{ $siteTagline }}</span>
                    @endif
                </a>
            </div>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center space-x-8">
                @foreach($menuItems as $item)
                    @php $itemUrl = $resolveMenuUrl($item); @endphp
                    @if(!empty($item['children']))
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <a href="{{ $itemUrl }}" class="text-gray-200 hover:text-white font-medium text-sm flex items-center gap-1">
                                {{ $item['label'] }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                            <div x-show="open" x-cloak class="absolute top-full left-0 w-48 pt-2 z-50">
                                <div class="bg-white rounded-lg shadow-lg border py-2">
                                    @foreach($item['children'] as $child)
                                        @php $childUrl = $resolveMenuUrl($child); @endphp
                                        <a href="{{ $childUrl }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                           @if(!empty($child['target']) && $child['target'] === '_blank') target="_blank" @endif
                                           @if(!empty($child['open_in_new_tab'])) target="_blank" @endif>
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ $itemUrl }}" class="text-gray-200 hover:text-white font-medium text-sm"
                           @if(!empty($item['target']) && $item['target'] === '_blank') target="_blank" @endif
                           @if(!empty($item['open_in_new_tab'])) target="_blank" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach

                {{-- CTA Button --}}
                <a href="{{ $fields['cta_url'] ?? '#contact' }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors"
                   style="background-color: var(--color-primary); color: var(--color-secondary)"
                   data-field="cta_text" data-field-type="text">
                    {{ $fields['cta_text'] ?? 'Get Started' }}
                </a>
            </div>

            {{-- Mobile Menu Button --}}
            <div class="md:hidden" x-data="{ mobileOpen: false }">
                <button @click="mobileOpen = !mobileOpen" class="text-gray-200 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- Mobile Menu --}}
                <div x-show="mobileOpen" x-cloak class="absolute top-16 left-0 right-0 shadow-lg border-t border-gray-700 z-50" style="background-color: var(--header-bg, #15171e)">
                    <div class="px-4 py-4 space-y-3">
                        @foreach($menuItems as $item)
                            @php $itemUrl = $resolveMenuUrl($item); @endphp
                            <a href="{{ $itemUrl }}" class="block text-gray-200 hover:text-white font-medium"
                               @if(!empty($item['target']) && $item['target'] === '_blank') target="_blank" @endif
                               @if(!empty($item['open_in_new_tab'])) target="_blank" @endif>
                                {{ $item['label'] }}
                            </a>
                            @if(!empty($item['children']))
                                @foreach($item['children'] as $child)
                                    @php $childUrl = $resolveMenuUrl($child); @endphp
                                    <a href="{{ $childUrl }}" class="block pl-4 text-gray-400 hover:text-gray-200 text-sm"
                                       @if(!empty($child['target']) && $child['target'] === '_blank') target="_blank" @endif
                                       @if(!empty($child['open_in_new_tab'])) target="_blank" @endif>
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            @endif
                        @endforeach
                        <a href="{{ $fields['cta_url'] ?? '#contact' }}" class="block text-center px-4 py-2 rounded-lg text-sm font-semibold text-white"
                           style="background-color: var(--color-primary); color: var(--color-secondary)">
                            {{ $fields['cta_text'] ?? 'Get Started' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
