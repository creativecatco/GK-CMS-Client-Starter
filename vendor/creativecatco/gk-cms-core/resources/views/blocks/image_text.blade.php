@php
    $heading = $block['data']['heading'] ?? '';
    $body = $block['data']['body'] ?? '';
    $image = $block['data']['image'] ?? '';
    $position = $block['data']['image_position'] ?? 'left';
    $ctaText = $block['data']['cta_text'] ?? '';
    $ctaLink = $block['data']['cta_link'] ?? '#';
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        'dark' => 'bg-gray-900 text-white',
        default => 'bg-white',
    };
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            {{-- Image --}}
            <div class="{{ $position === 'right' ? 'md:order-2' : '' }}">
                @if($image)
                    <img src="{{ asset('storage/' . $image) }}" alt="{{ $heading }}" class="w-full rounded-xl shadow-lg" loading="lazy">
                @else
                    <div class="w-full aspect-video bg-gray-200 rounded-xl flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                    </div>
                @endif
            </div>

            {{-- Text --}}
            <div class="{{ $position === 'right' ? 'md:order-1' : '' }}">
                @if($heading)
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 {{ $bgColor === 'dark' ? 'text-white' : 'text-gray-900' }}">
                        {{ $heading }}
                    </h2>
                @endif

                @if($body)
                    <div class="prose max-w-none mb-8 {{ $bgColor === 'dark' ? 'prose-invert' : '' }}">
                        {!! $body !!}
                    </div>
                @endif

                @if($ctaText)
                    <a href="{{ $ctaLink }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                        {{ $ctaText }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
