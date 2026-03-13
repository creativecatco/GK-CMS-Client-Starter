{{-- 
    Single blog post template for the client theme.
    Override this file to customize individual blog post pages.
    
    Available variables:
    - $post: The Post Eloquent model (with author, categories, tags)
    - $seo: Array of SEO data
    - $relatedPosts: Collection of related posts
--}}

@extends('cms-core::layouts.app')

@section('content')
<article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-8">
        @if($post->categories->count())
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($post->categories as $category)
                    <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                        {{ $category->name }}
                    </span>
                @endforeach
            </div>
        @endif

        <h1 class="text-4xl font-bold tracking-tight text-gray-900">
            {{ $post->title }}
        </h1>

        <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
            <span>By {{ $post->author?->name ?? 'Unknown' }}</span>
            <span>&middot;</span>
            <time datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->format('F j, Y') }}
            </time>
        </div>
    </header>

    @if($post->featured_image)
        <div class="mb-8">
            <img
                src="{{ asset($post->featured_image) }}"
                alt="{{ $post->title }}"
                class="w-full rounded-lg shadow-md"
            >
        </div>
    @endif

    <div class="prose prose-lg max-w-none">
        {!! $post->content !!}
    </div>

    @if($post->tags->count())
        <div class="mt-8 border-t pt-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Tags</h3>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($post->tags as $tag)
                    <span class="inline-block rounded bg-gray-100 px-3 py-1 text-sm text-gray-700">
                        #{{ $tag->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($relatedPosts) && $relatedPosts->count())
        <div class="mt-12 border-t pt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Posts</h2>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach($relatedPosts as $related)
                    <a href="{{ route('cms.blog.show', $related->slug) }}" class="group block">
                        @if($related->featured_image)
                            <img
                                src="{{ asset($related->featured_image) }}"
                                alt="{{ $related->title }}"
                                class="mb-3 h-32 w-full rounded-lg object-cover transition-opacity group-hover:opacity-80"
                            >
                        @endif
                        <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                            {{ $related->title }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $related->published_at?->format('M d, Y') }}
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</article>
@endsection
