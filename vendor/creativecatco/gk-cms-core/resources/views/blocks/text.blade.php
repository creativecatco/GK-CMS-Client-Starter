@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $body = $block['data']['body'] ?? '';
    $maxWidth = $block['data']['max_width'] ?? 'medium';
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $widthClass = match($maxWidth) {
        'narrow' => 'max-w-3xl',
        'medium' => 'max-w-5xl',
        'wide' => 'max-w-7xl',
        'full' => 'max-w-full',
        default => 'max-w-5xl',
    };

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        'dark' => 'bg-gray-900 text-white',
        'primary' => 'bg-blue-600 text-white',
        default => 'bg-white',
    };
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="{{ $widthClass }} mx-auto px-6">
        @if($heading)
            <h2 class="text-3xl md:text-4xl font-bold mb-3 {{ $bgColor === 'dark' || $bgColor === 'primary' ? 'text-white' : 'text-gray-900' }}">
                {{ $heading }}
            </h2>
        @endif

        @if($subheading)
            <p class="text-lg mb-8 {{ $bgColor === 'dark' || $bgColor === 'primary' ? 'text-gray-300' : 'text-gray-600' }}">
                {{ $subheading }}
            </p>
        @endif

        @if($body)
            <div class="prose max-w-none {{ $bgColor === 'dark' ? 'prose-invert' : '' }}">
                {!! $body !!}
            </div>
        @endif
    </div>
</section>
