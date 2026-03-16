@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $columns = $block['data']['columns'] ?? '3';
    $members = $block['data']['members'] ?? [];
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

        @if(count($members))
            <div class="grid {{ $colClass }} gap-8">
                @foreach($members as $member)
                    <div class="text-center group">
                        <div class="mb-4 mx-auto w-40 h-40 rounded-full overflow-hidden bg-gray-200">
                            @if(!empty($member['photo']))
                                <img src="{{ asset('storage/' . $member['photo']) }}" alt="{{ $member['name'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 text-4xl font-bold">
                                    {{ strtoupper(substr($member['name'] ?? 'T', 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        @if(!empty($member['name']))
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $member['name'] }}</h3>
                        @endif

                        @if(!empty($member['role']))
                            <p class="text-blue-600 font-medium mb-2">{{ $member['role'] }}</p>
                        @endif

                        @if(!empty($member['bio']))
                            <p class="text-gray-600 text-sm mb-3">{{ $member['bio'] }}</p>
                        @endif

                        @if(!empty($member['linkedin']) || !empty($member['email']))
                            <div class="flex items-center justify-center gap-3">
                                @if(!empty($member['linkedin']))
                                    <a href="{{ $member['linkedin'] }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                    </a>
                                @endif
                                @if(!empty($member['email']))
                                    <a href="mailto:{{ $member['email'] }}" class="text-gray-400 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
