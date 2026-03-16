@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $columns = $block['data']['columns'] ?? '3';
    $images = $block['data']['images'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        default => 'bg-white',
    };

    $colClass = match($columns) {
        '2' => 'md:grid-cols-2',
        '4' => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6">
        @if($heading || $subheading)
            <div class="text-center mb-12">
                @if($heading)
                    <h2 class="text-3xl md:text-4xl font-bold mb-3 text-gray-900">{{ $heading }}</h2>
                @endif
                @if($subheading)
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        @if(count($images))
            <div class="grid {{ $colClass }} gap-4">
                @foreach($images as $img)
                    <div class="group relative overflow-hidden rounded-xl">
                        @if(!empty($img['image']))
                            <img
                                src="{{ asset('storage/' . $img['image']) }}"
                                alt="{{ $img['alt'] ?? $img['caption'] ?? '' }}"
                                class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            >
                        @endif
                        @if(!empty($img['caption']))
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                <p class="text-white text-sm">{{ $img['caption'] }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
