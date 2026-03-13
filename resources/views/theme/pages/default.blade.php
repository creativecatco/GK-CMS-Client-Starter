{{-- 
    Default page template for the client theme.
    Override this file to customize the look and feel of CMS pages.
    
    Available variables:
    - $page: The Page Eloquent model
    - $seo: Array of SEO data (title, meta, OG, Twitter, JSON-LD)
--}}

@extends('cms-core::layouts.app')

@section('content')
<article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-8">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900">
            {{ $page->title }}
        </h1>
    </header>

    @if($page->featured_image)
        <div class="mb-8">
            <img
                src="{{ asset($page->featured_image) }}"
                alt="{{ $page->title }}"
                class="w-full rounded-lg shadow-md"
            >
        </div>
    @endif

    <div class="prose prose-lg max-w-none">
        {!! $page->content !!}
    </div>
</article>
@endsection
