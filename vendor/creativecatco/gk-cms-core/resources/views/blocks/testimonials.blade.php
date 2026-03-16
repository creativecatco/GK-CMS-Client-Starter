@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $items = $block['data']['items'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'gray';

    $bgClass = match($bgColor) {
        'white' => 'bg-white',
        'dark' => 'bg-gray-900 text-white',
        default => 'bg-gray-50',
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
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($items as $item)
                    <div class="p-6 rounded-xl {{ $bgColor === 'dark' ? 'bg-gray-800' : 'bg-white shadow-md border border-gray-100' }}">
                        {{-- Star Rating --}}
                        @if(!empty($item['rating']))
                            <div class="flex gap-1 mb-4">
                                @for($i = 0; $i < (int)$item['rating']; $i++)
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                        @endif

                        {{-- Quote --}}
                        @if(!empty($item['quote']))
                            <blockquote class="text-base mb-6 {{ $bgColor === 'dark' ? 'text-gray-300' : 'text-gray-600' }} italic leading-relaxed">
                                "{{ $item['quote'] }}"
                            </blockquote>
                        @endif

                        {{-- Author --}}
                        <div class="flex items-center gap-3">
                            @if(!empty($item['avatar']))
                                <img src="{{ asset('storage/' . $item['avatar']) }}" alt="{{ $item['author'] ?? '' }}" class="w-12 h-12 rounded-full object-cover" loading="lazy">
                            @else
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                                    {{ strtoupper(substr($item['author'] ?? 'A', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-semibold {{ $bgColor === 'dark' ? 'text-white' : 'text-gray-900' }}">
                                    {{ $item['author'] ?? '' }}
                                </div>
                                @if(!empty($item['role']))
                                    <div class="text-sm {{ $bgColor === 'dark' ? 'text-gray-400' : 'text-gray-500' }}">
                                        {{ $item['role'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
