@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $plans = $block['data']['plans'] ?? [];
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        default => 'bg-white',
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

        @if(count($plans))
            <div class="grid md:grid-cols-{{ min(count($plans), 3) }} gap-8 items-start">
                @foreach($plans as $plan)
                    @php
                        $highlighted = !empty($plan['highlighted']);
                        $features = array_filter(explode("\n", $plan['features'] ?? ''));
                    @endphp
                    <div class="relative rounded-2xl p-8 {{ $highlighted ? 'bg-blue-600 text-white shadow-2xl ring-4 ring-blue-600/20 scale-105' : 'bg-white shadow-lg border border-gray-200' }}">
                        @if($highlighted)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-yellow-400 text-yellow-900 text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wide">
                                Most Popular
                            </div>
                        @endif

                        @if(!empty($plan['name']))
                            <h3 class="text-xl font-bold mb-2 {{ $highlighted ? 'text-white' : 'text-gray-900' }}">
                                {{ $plan['name'] }}
                            </h3>
                        @endif

                        @if(!empty($plan['description']))
                            <p class="text-sm mb-4 {{ $highlighted ? 'text-blue-100' : 'text-gray-500' }}">
                                {{ $plan['description'] }}
                            </p>
                        @endif

                        <div class="mb-6">
                            <span class="text-4xl font-extrabold {{ $highlighted ? 'text-white' : 'text-gray-900' }}">
                                {{ $plan['price'] ?? '' }}
                            </span>
                            @if(!empty($plan['period']))
                                <span class="text-sm {{ $highlighted ? 'text-blue-200' : 'text-gray-500' }}">
                                    {{ $plan['period'] }}
                                </span>
                            @endif
                        </div>

                        @if(count($features))
                            <ul class="space-y-3 mb-8">
                                @foreach($features as $feature)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $highlighted ? 'text-blue-200' : 'text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                        <span class="{{ $highlighted ? 'text-blue-100' : 'text-gray-600' }}">{{ trim($feature) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($plan['cta_text']))
                            <a href="{{ $plan['cta_link'] ?? '#' }}" class="block w-full text-center px-6 py-3 font-semibold rounded-lg transition-colors {{ $highlighted ? 'bg-white text-blue-600 hover:bg-gray-100' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                                {{ $plan['cta_text'] }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
