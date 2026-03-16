@extends('cms-core::layouts.app')

@section('content')
    @if(!empty($renderedBlocks))
        {{-- Block-based content --}}
        {!! $renderedBlocks !!}
    @elseif($page && !empty($fields))
        {{-- Editable fields content --}}
        <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900"
                    data-field="title" data-field-type="text" data-field-label="Page Title">
                    {{ $fields['title'] ?? $page->title }}
                </h1>
                @if(!empty($fields['subtitle']))
                    <p class="mt-3 text-xl text-gray-600"
                       data-field="subtitle" data-field-type="text" data-field-label="Subtitle">
                        {{ $fields['subtitle'] }}
                    </p>
                @endif
            </header>

            @php
                $defImg = !empty($fields['hero_image']) ? $fields['hero_image'] : ($page->featured_image ?? null);
                $defImgUrl = $defImg ? (str_starts_with($defImg, 'http') ? $defImg : asset('storage/' . $defImg)) : null;
            @endphp
            @if($defImgUrl)
                <div class="mb-8">
                    <img
                        src="{{ $defImgUrl }}"
                        alt="{{ $fields['title'] ?? $page->title }}"
                        class="w-full rounded-lg shadow-md"
                        data-field="hero_image" data-field-type="image" data-field-label="Hero Image"
                    >
                </div>
            @endif

            @if(!empty($fields['body']))
                <div class="prose prose-lg max-w-none"
                     data-field="body" data-field-type="richtext" data-field-label="Body Content">
                    {!! $fields['body'] !!}
                </div>
            @endif
        </article>
    @elseif($page)
        {{-- Legacy rich text content --}}
        <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900">
                    {{ $page->title }}
                </h1>
            </header>

            @php
                $legImg = $page->featured_image ?? null;
                $legImgUrl = $legImg ? (str_starts_with($legImg, 'http') ? $legImg : asset('storage/' . $legImg)) : null;
            @endphp
            @if($legImgUrl)
                <div class="mb-8">
                    <img
                        src="{{ $legImgUrl }}"
                        alt="{{ $page->title }}"
                        class="w-full rounded-lg shadow-md"
                    >
                </div>
            @endif

            <div class="prose prose-lg max-w-none">
                {!! $page->content !!}
            </div>
        </article>
    @else
        {{-- No page found placeholder --}}
        <div class="mx-auto max-w-4xl px-4 py-20 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome</h1>
            <p class="text-lg text-gray-600">This site is powered by GK CMS. Create your first page in the admin panel.</p>
            <a href="/admin" class="inline-block mt-6 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                Go to Admin Panel
            </a>
        </div>
    @endif
@endsection
