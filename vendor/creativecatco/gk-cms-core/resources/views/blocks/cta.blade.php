@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $ctaText = $block['data']['cta_text'] ?? '';
    $ctaLink = $block['data']['cta_link'] ?? '#';
    $ctaText2 = $block['data']['cta_text_2'] ?? '';
    $ctaLink2 = $block['data']['cta_link_2'] ?? '#';
    $style = $block['data']['style'] ?? 'primary';

    $bgClass = match($style) {
        'dark' => 'bg-gray-900',
        'gradient' => 'bg-gradient-to-r from-blue-600 to-indigo-700',
        'light' => 'bg-gray-50',
        default => 'bg-blue-600',
    };

    $textColor = $style === 'light' ? 'text-gray-900' : 'text-white';
    $subColor = $style === 'light' ? 'text-gray-600' : 'text-blue-100';
    $btnPrimary = $style === 'light'
        ? 'bg-blue-600 text-white hover:bg-blue-700'
        : 'bg-white text-gray-900 hover:bg-gray-100';
    $btnSecondary = $style === 'light'
        ? 'border-2 border-gray-300 text-gray-700 hover:bg-gray-100'
        : 'border-2 border-white text-white hover:bg-white/10';
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-6 text-center">
        @if($heading)
            <h2 class="text-3xl md:text-4xl font-bold mb-4 {{ $textColor }}">
                {{ $heading }}
            </h2>
        @endif

        @if($subheading)
            <p class="text-lg mb-8 {{ $subColor }} max-w-2xl mx-auto">
                {{ $subheading }}
            </p>
        @endif

        @if($ctaText || $ctaText2)
            <div class="flex flex-wrap gap-4 justify-center">
                @if($ctaText)
                    <a href="{{ $ctaLink }}" class="inline-block px-8 py-3 font-semibold rounded-lg transition-colors shadow-lg {{ $btnPrimary }}">
                        {{ $ctaText }}
                    </a>
                @endif
                @if($ctaText2)
                    <a href="{{ $ctaLink2 }}" class="inline-block px-8 py-3 font-semibold rounded-lg transition-colors {{ $btnSecondary }}">
                        {{ $ctaText2 }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
