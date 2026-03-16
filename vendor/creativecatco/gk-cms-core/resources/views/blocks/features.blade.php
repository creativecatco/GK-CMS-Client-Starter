@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $columns = $block['data']['columns'] ?? '3';
    $items = $block['data']['items'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        'dark' => 'bg-gray-900 text-white',
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

        @if(count($items))
            <div class="grid {{ $colClass }} gap-8">
                @foreach($items as $item)
                    @php
                        $hasLink = !empty($item['link']);
                        $tag = $hasLink ? 'a' : 'div';
                    @endphp
                    <{{ $tag }}
                        @if($hasLink) href="{{ $item['link'] }}" @endif
                        class="group p-6 rounded-xl {{ $bgColor === 'dark' ? 'bg-gray-800 hover:bg-gray-700' : 'bg-white shadow-md hover:shadow-lg border border-gray-100' }} transition-all"
                    >
                        @if(!empty($item['image']))
                            <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-48 object-cover rounded-lg mb-4" loading="lazy">
                        @elseif(!empty($item['icon']))
                            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                                </svg>
                            </div>
                        @endif

                        @if(!empty($item['title']))
                            <h3 class="text-xl font-semibold mb-2 {{ $bgColor === 'dark' ? 'text-white' : 'text-gray-900' }}">
                                {{ $item['title'] }}
                            </h3>
                        @endif

                        @if(!empty($item['description']))
                            <p class="{{ $bgColor === 'dark' ? 'text-gray-300' : 'text-gray-600' }}">
                                {{ $item['description'] }}
                            </p>
                        @endif
                    </{{ $tag }}>
                @endforeach
            </div>
        @endif
    </div>
</section>
