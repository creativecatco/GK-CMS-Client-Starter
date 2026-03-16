@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $videoUrl = $block['data']['video_url'] ?? '';
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        'dark' => 'bg-gray-900 text-white',
        default => 'bg-white',
    };

    // Convert YouTube/Vimeo URLs to embed format
    $embedUrl = '';
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
        $embedUrl = 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
    } elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
        $embedUrl = 'https://player.vimeo.com/video/' . $matches[1];
    }
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-5xl mx-auto px-6">
        @if($heading || $subheading)
            <div class="text-center mb-10">
                @if($heading)
                    <h2 class="text-3xl md:text-4xl font-bold mb-3 {{ $bgColor === 'dark' ? 'text-white' : 'text-gray-900' }}">
                        {{ $heading }}
                    </h2>
                @endif
                @if($subheading)
                    <p class="text-lg {{ $bgColor === 'dark' ? 'text-gray-300' : 'text-gray-600' }} max-w-2xl mx-auto">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        @if($embedUrl)
            <div class="relative w-full rounded-xl overflow-hidden shadow-2xl" style="padding-bottom: 56.25%;">
                <iframe
                    src="{{ $embedUrl }}"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        @elseif($videoUrl)
            <p class="text-center text-gray-500">Unsupported video URL. Please use a YouTube or Vimeo link.</p>
        @endif
    </div>
</section>
