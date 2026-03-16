@php
    $headline = $block['data']['headline'] ?? '';
    $subheadline = $block['data']['subheadline'] ?? '';
    $bgImage = $block['data']['background_image'] ?? '';
    $overlay = $block['data']['overlay_style'] ?? 'dark';
    $ctaText = $block['data']['cta_text'] ?? '';
    $ctaLink = $block['data']['cta_link'] ?? '#';
    $ctaText2 = $block['data']['cta_text_2'] ?? '';
    $ctaLink2 = $block['data']['cta_link_2'] ?? '#';
    $alignment = $block['data']['text_alignment'] ?? 'center';
    $minHeight = $block['data']['min_height'] ?? 'large';

    $heightClass = match($minHeight) {
        'screen' => 'min-h-screen',
        'large' => 'min-h-[80vh]',
        'medium' => 'min-h-[60vh]',
        'small' => 'min-h-[40vh]',
        default => 'min-h-[80vh]',
    };

    $alignClass = match($alignment) {
        'left' => 'text-left items-start',
        'right' => 'text-right items-end',
        default => 'text-center items-center',
    };

    $overlayClass = match($overlay) {
        'dark' => 'bg-black/60',
        'light' => 'bg-white/40',
        'gradient' => 'bg-gradient-to-b from-black/70 via-black/40 to-black/70',
        default => '',
    };
@endphp

<section class="relative {{ $heightClass }} flex items-center justify-center overflow-hidden">
    @if($bgImage)
        <img src="{{ asset('storage/' . $bgImage) }}" alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900"></div>
    @endif

    @if($overlay !== 'none')
        <div class="absolute inset-0 {{ $overlayClass }}"></div>
    @endif

    <div class="relative z-10 max-w-5xl mx-auto px-6 py-20 flex flex-col {{ $alignClass }}">
        @if($headline)
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
                {{ $headline }}
            </h1>
        @endif

        @if($subheadline)
            <p class="text-lg md:text-xl text-gray-200 max-w-2xl mb-8 {{ $alignment === 'center' ? 'mx-auto' : '' }}">
                {{ $subheadline }}
            </p>
        @endif

        @if($ctaText || $ctaText2)
            <div class="flex flex-wrap gap-4 {{ $alignment === 'center' ? 'justify-center' : '' }}">
                @if($ctaText)
                    <a href="{{ $ctaLink }}" class="inline-block px-8 py-3 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition-colors shadow-lg">
                        {{ $ctaText }}
                    </a>
                @endif
                @if($ctaText2)
                    <a href="{{ $ctaLink2 }}" class="inline-block px-8 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition-colors">
                        {{ $ctaText2 }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
