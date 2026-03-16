{{--
    GKeys CMS - Default Blog Post Template
    page_type: post

    Fields:
    - post_title (text): Post title (also used as H1)
    - featured_image (image): Featured/hero image
    - excerpt (textarea): Short excerpt for listings
    - category (text): Post category
    - author (text): Author name
    - content (richtext): Full post content
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Post Header --}}
<article>
    <header class="py-16" style="background-color: var(--color-secondary)"
        data-section-bg="post_header_bg" data-section-bg-type="color">
        <div class="max-w-4xl mx-auto px-4 text-center">
            @if(!empty($fields['category']))
                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full mb-4" style="background-color: var(--color-primary); color: var(--color-secondary)"
                      data-field="category" data-field-type="text">{{ $fields['category'] }}</span>
            @endif
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4" data-field="post_title" data-field-type="text">{{ $fields['post_title'] ?? $page->title ?? 'Blog Post Title' }}</h1>
            <div class="flex items-center justify-center gap-4 text-gray-400 text-sm">
                <span data-field="author" data-field-type="text">{{ $fields['author'] ?? 'Admin' }}</span>
                <span>&middot;</span>
                <time>{{ $page->created_at->format('F d, Y') ?? now()->format('F d, Y') }}</time>
            </div>
        </div>
    </header>

    {{-- Featured Image --}}
    @if(!empty($fields['featured_image']))
        <div class="max-w-4xl mx-auto px-4 -mt-8">
            <img src="{{ str_starts_with($fields['featured_image'], 'http') ? $fields['featured_image'] : asset('storage/' . $fields['featured_image']) }}" alt="{{ $fields['post_title'] ?? 'Featured image' }}" class="w-full rounded-xl shadow-lg aspect-video object-cover" data-field="featured_image" data-field-type="image">
        </div>
    @endif

    {{-- Post Content --}}
    <div class="max-w-3xl mx-auto px-4 py-12">
        <div class="prose prose-lg max-w-none" data-field="content" data-field-type="richtext">
            {!! $fields['content'] ?? '<p>Start writing your blog post here. Use the inline editor to add rich content including headings, images, links, and more.</p><p>This is a sample blog post template. Replace this content with your own.</p>' !!}
        </div>
    </div>

    {{-- Post Navigation --}}
    <div class="max-w-3xl mx-auto px-4 pb-12">
        <div class="border-t pt-8">
            <a href="/blog" class="text-sm font-semibold hover:underline" style="color: var(--color-primary)">&larr; Back to Blog</a>
        </div>
    </div>
</article>
@endsection
