{{--
    GKeys CMS - Single Portfolio Item Template
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Hero / Featured Image --}}
@php
    $pfSingleImg = $portfolio->featured_image ?? null;
    $pfSingleImgUrl = $pfSingleImg ? (str_starts_with($pfSingleImg, 'http') ? $pfSingleImg : asset('storage/' . $pfSingleImg)) : null;
@endphp
<section class="relative" style="background-color: var(--color-secondary)">
    @if($pfSingleImgUrl)
        <div class="max-w-7xl mx-auto">
            <img src="{{ $pfSingleImgUrl }}" alt="{{ $portfolio->title }}"
                 class="w-full max-h-[500px] object-cover">
        </div>
    @else
        <div class="py-20"></div>
    @endif
</section>

{{-- Content --}}
<section class="py-16" style="background-color: var(--color-bg)">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Title & Meta --}}
        <div class="mb-8">
            @if($portfolio->categories->count() > 0)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($portfolio->categories as $cat)
                        <span class="text-xs font-medium px-3 py-1 rounded-full"
                              style="background-color: color-mix(in srgb, var(--color-primary) 15%, white); color: var(--color-secondary)">
                            {{ $cat->name }}
                        </span>
                    @endforeach
                </div>
            @endif
            <h1 class="text-3xl md:text-4xl font-extrabold mb-4">{{ $portfolio->title }}</h1>

            {{-- Project Details --}}
            <div class="flex flex-wrap gap-6 text-sm text-gray-500">
                @if($portfolio->client)
                    <div><span class="font-medium text-gray-700">Client:</span> {{ $portfolio->client }}</div>
                @endif
                @if($portfolio->project_date)
                    <div><span class="font-medium text-gray-700">Date:</span> {{ $portfolio->project_date->format('F Y') }}</div>
                @endif
                @if($portfolio->project_url)
                    <div>
                        <a href="{{ $portfolio->project_url }}" target="_blank" rel="noopener"
                           class="font-medium hover:underline" style="color: var(--color-primary)">
                            View Live Project &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Content --}}
        @if($portfolio->content)
            <div class="prose prose-lg max-w-none mb-12">
                {!! $portfolio->content !!}
            </div>
        @endif

        {{-- Gallery --}}
        @if($portfolio->gallery && count($portfolio->gallery) > 0)
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-6">Project Gallery</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($portfolio->gallery as $image)
                        <img src="{{ str_starts_with($image, 'http') ? $image : asset('storage/' . $image) }}"
                             alt="{{ $portfolio->title }} gallery"
                             class="rounded-lg w-full object-cover aspect-video cursor-pointer hover:opacity-90 transition-opacity">
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Back Link --}}
        <div class="border-t pt-8 mt-8">
            <a href="/portfolio" class="inline-flex items-center gap-2 font-semibold hover:underline" style="color: var(--color-secondary)">
                &larr; Back to Portfolio
            </a>
        </div>
    </div>
</section>

{{-- Related Projects --}}
@if(isset($related) && $related->count() > 0)
<section class="py-16 border-t" style="background-color: #f6f1e6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-8 text-center">More Projects</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($related as $item)
                @php
                    $relPfImg = $item->featured_image ?? null;
                    $relPfImgUrl = $relPfImg ? (str_starts_with($relPfImg, 'http') ? $relPfImg : asset('storage/' . $relPfImg)) : null;
                @endphp
                <article class="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                    <a href="/portfolio/{{ $item->slug }}" class="block">
                        @if($relPfImgUrl)
                            <img src="{{ $relPfImgUrl }}" alt="{{ $item->title }}"
                                 class="w-full aspect-[4/3] object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full aspect-[4/3] flex items-center justify-center" style="background-color: var(--color-secondary)">
                                <span class="text-white text-4xl font-bold opacity-20">{{ strtoupper(substr($item->title, 0, 2)) }}</span>
                            </div>
                        @endif
                    </a>
                    <div class="p-5">
                        <h3 class="font-semibold"><a href="/portfolio/{{ $item->slug }}" class="hover:underline">{{ $item->title }}</a></h3>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
