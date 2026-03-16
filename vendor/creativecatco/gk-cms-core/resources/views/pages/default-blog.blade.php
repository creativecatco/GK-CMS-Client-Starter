{{--
    GKeys CMS - Default Blog Listing Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)

    Receives:
    - $posts: Paginated native Post models (from posts table, may be null)
    - $pagePosts: Collection of Page models with page_type='post'
    - $page: The blog listing page itself
    - $fields: Blog page fields
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'Blog' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'Insights, tips, and stories to help you grow your business.' }}</p>
    </div>
</section>

{{-- Blog Posts Grid --}}
<section class="py-20" data-section-bg="blog_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $hasNativePosts = isset($posts) && $posts && $posts->count() > 0;
            $hasPagePosts = isset($pagePosts) && $pagePosts->count() > 0;
        @endphp

        @if($hasNativePosts || $hasPagePosts)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                {{-- Native Posts (from posts table) --}}
                @if($hasNativePosts)
                    @foreach($posts as $post)
                        @php
                            $postImg = $post->featured_image;
                            $postImgUrl = $postImg ? (str_starts_with($postImg, 'http') ? $postImg : asset('storage/' . $postImg)) : null;
                        @endphp
                        <article class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <a href="/blog/{{ $post->slug }}">
                                @if($postImgUrl)
                                    <img src="{{ $postImgUrl }}"
                                         alt="{{ $post->title }}" class="w-full aspect-video object-cover">
                                @else
                                    <div class="w-full aspect-video flex items-center justify-center" style="background-color: var(--color-secondary)">
                                        <span class="text-white text-4xl font-bold opacity-30">{{ strtoupper(substr($post->title, 0, 2)) }}</span>
                                    </div>
                                @endif
                            </a>
                            <div class="p-6">
                                <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                    @if($post->published_at)
                                        <time datetime="{{ $post->published_at->toISOString() }}">{{ $post->published_at->format('M d, Y') }}</time>
                                    @endif
                                </div>
                                <h2 class="text-xl font-semibold mb-2">
                                    <a href="/blog/{{ $post->slug }}" class="hover:underline" style="color: var(--color-text)">{{ $post->title }}</a>
                                </h2>
                                <p class="text-gray-600 text-sm">{{ $post->excerpt ?: Str::limit(strip_tags($post->content ?? ''), 120) }}</p>
                                <a href="/blog/{{ $post->slug }}" class="inline-block mt-4 text-sm font-semibold hover:underline" style="color: var(--color-primary)">Read More &rarr;</a>
                            </div>
                        </article>
                    @endforeach
                @endif

                {{-- Page-based Posts (from pages table with page_type='post') --}}
                @if($hasPagePosts)
                    @foreach($pagePosts as $pagePost)
                        @php
                            $postFields = $pagePost->fields ?? [];
                            $featuredImage = $postFields['featured_image'] ?? '';
                            $excerpt = $postFields['excerpt'] ?? Str::limit(strip_tags($postFields['content'] ?? ''), 120);
                        @endphp
                        <article class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <a href="/{{ $pagePost->slug }}">
                                @if($featuredImage)
                                    <img src="{{ str_starts_with($featuredImage, 'http') ? $featuredImage : asset('storage/' . $featuredImage) }}"
                                         alt="{{ $pagePost->title }}" class="w-full aspect-video object-cover">
                                @else
                                    <div class="w-full aspect-video flex items-center justify-center" style="background-color: var(--color-secondary)">
                                        <span class="text-white text-4xl font-bold opacity-30">{{ strtoupper(substr($pagePost->title, 0, 2)) }}</span>
                                    </div>
                                @endif
                            </a>
                            <div class="p-6">
                                <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                    <time>{{ $pagePost->created_at->format('M d, Y') }}</time>
                                    @if(!empty($postFields['category']))
                                        <span>&middot;</span>
                                        <span style="color: var(--color-primary)">{{ $postFields['category'] }}</span>
                                    @endif
                                </div>
                                <h2 class="text-xl font-semibold mb-2">
                                    <a href="/{{ $pagePost->slug }}" class="hover:underline" style="color: var(--color-text)">{{ $pagePost->title }}</a>
                                </h2>
                                <p class="text-gray-600 text-sm">{{ $excerpt }}</p>
                                <a href="/{{ $pagePost->slug }}" class="inline-block mt-4 text-sm font-semibold hover:underline" style="color: var(--color-primary)">Read More &rarr;</a>
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>

            {{-- Pagination (for native posts) --}}
            @if($hasNativePosts && $posts->hasPages())
                <div class="mt-12">
                    {{ $posts->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6V7.5Z" />
                </svg>
                <p class="text-gray-500 text-lg">No blog posts yet. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
