{{--
    GKeys CMS - Portfolio Archive Template
    Displays a filterable grid of published portfolio items.
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">Portfolio</h1>
        <p class="text-xl text-gray-300">A showcase of our recent work and projects.</p>
    </div>
</section>

{{-- Category Filter --}}
@if(isset($categories) && $categories->count() > 0)
<section class="py-6 border-b" style="background-color: var(--color-bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-2 justify-center" x-data="{ active: 'all' }">
            <button @click="active = 'all'"
                    :class="active === 'all' ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    :style="active === 'all' ? 'background-color: var(--color-secondary)' : ''"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-colors">
                All
            </button>
            @foreach($categories as $category)
                <button @click="active = '{{ $category->slug }}'"
                        :class="active === '{{ $category->slug }}' ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        :style="active === '{{ $category->slug }}' ? 'background-color: var(--color-secondary)' : ''"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Portfolio Grid --}}
<section class="py-16" style="background-color: var(--color-bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($portfolios->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($portfolios as $portfolio)
                    @php
                        $pfImg = $portfolio->featured_image ?? null;
                        $pfImgUrl = $pfImg ? (str_starts_with($pfImg, 'http') ? $pfImg : asset('storage/' . $pfImg)) : null;
                    @endphp
                    <article class="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all">
                        <a href="/portfolio/{{ $portfolio->slug }}" class="block relative overflow-hidden">
                            @if($pfImgUrl)
                                <img src="{{ $pfImgUrl }}"
                                     alt="{{ $portfolio->title }}"
                                     class="w-full aspect-[4/3] object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full aspect-[4/3] flex items-center justify-center" style="background-color: var(--color-secondary)">
                                    <span class="text-white text-5xl font-bold opacity-20">{{ strtoupper(substr($portfolio->title, 0, 2)) }}</span>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                        </a>
                        <div class="p-6">
                            @if($portfolio->categories->count() > 0)
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach($portfolio->categories as $cat)
                                        <span class="text-xs font-medium px-2 py-1 rounded-full"
                                              style="background-color: color-mix(in srgb, var(--color-primary) 15%, white); color: var(--color-secondary)">
                                            {{ $cat->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <h2 class="text-xl font-semibold mb-2">
                                <a href="/portfolio/{{ $portfolio->slug }}" class="hover:underline" style="color: var(--color-text)">{{ $portfolio->title }}</a>
                            </h2>
                            @if($portfolio->excerpt)
                                <p class="text-gray-600 text-sm">{{ Str::limit($portfolio->excerpt, 100) }}</p>
                            @endif
                            @if($portfolio->client)
                                <p class="text-xs text-gray-400 mt-3">Client: {{ $portfolio->client }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if($portfolios->hasPages())
                <div class="mt-12">
                    {{ $portfolios->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v13.5A1.5 1.5 0 0 0 3.75 21Z" />
                </svg>
                <p class="text-gray-500 text-lg">No portfolio items yet. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
