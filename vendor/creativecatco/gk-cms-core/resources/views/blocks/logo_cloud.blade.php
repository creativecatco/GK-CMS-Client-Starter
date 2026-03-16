@php
    $heading = $block['data']['heading'] ?? '';
    $logos = $block['data']['logos'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'gray';

    $bgClass = match($bgColor) {
        'white' => 'bg-white',
        'dark' => 'bg-gray-900',
        default => 'bg-gray-50',
    };
@endphp

<section class="{{ $bgClass }} py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-6">
        @if($heading)
            <p class="text-center text-sm font-semibold uppercase tracking-wider mb-8 {{ $bgColor === 'dark' ? 'text-gray-400' : 'text-gray-500' }}">
                {{ $heading }}
            </p>
        @endif

        @if(count($logos))
            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
                @foreach($logos as $logo)
                    @if(!empty($logo['logo']))
                        @if(!empty($logo['url']))
                            <a href="{{ $logo['url'] }}" target="_blank" rel="noopener" class="block">
                        @endif
                        <img
                            src="{{ asset('storage/' . $logo['logo']) }}"
                            alt="{{ $logo['name'] ?? 'Client logo' }}"
                            class="h-10 md:h-12 w-auto object-contain {{ $bgColor === 'dark' ? 'brightness-0 invert opacity-60 hover:opacity-100' : 'grayscale opacity-60 hover:grayscale-0 hover:opacity-100' }} transition-all"
                            loading="lazy"
                        >
                        @if(!empty($logo['url']))
                            </a>
                        @endif
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
