@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $items = $block['data']['items'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'white';
    $blockId = 'faq-' . ($loop->index ?? rand(1000, 9999));

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        default => 'bg-white',
    };
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-3xl mx-auto px-6">
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

        @if(count($items))
            <div class="space-y-4">
                @foreach($items as $index => $item)
                    <div class="border border-gray-200 rounded-lg overflow-hidden" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                        <button
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-6 py-4 text-left font-semibold text-gray-900 hover:bg-gray-50 transition-colors"
                        >
                            <span>{{ $item['question'] ?? '' }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="px-6 pb-4">
                            <div class="prose max-w-none text-gray-600">
                                {!! $item['answer'] ?? '' !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
