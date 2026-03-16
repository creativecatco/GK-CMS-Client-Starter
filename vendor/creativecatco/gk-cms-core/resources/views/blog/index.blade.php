@extends('cms-core::layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-12">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900">Blog</h1>
        <p class="mt-2 text-lg text-gray-600">Latest articles and updates.</p>
    </header>

    @if($posts->count())
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                @php
                    $idxImg = $post->featured_image;
                    $idxImgUrl = $idxImg ? (str_starts_with($idxImg, 'http') ? $idxImg : asset('storage/' . $idxImg)) : null;
                @endphp
                <article class="flex flex-col overflow-hidden rounded-lg shadow-md transition-shadow hover:shadow-lg">
                    @if($idxImgUrl)
                        <a href="{{ route('cms.blog.show', $post->slug) }}">
                            <img
                                src="{{ $idxImgUrl }}"
                                alt="{{ $post->title }}"
                                class="h-48 w-full object-cover"
                            >
                        </a>
                    @endif

                    <div class="flex flex-1 flex-col p-6">
                        {{-- Categories --}}
                        @if($post->categories->count())
                            <div class="mb-2 flex flex-wrap gap-1">
                                @foreach($post->categories as $category)
                                    <span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <h2 class="mb-2 text-xl font-semibold text-gray-900">
                            <a href="{{ route('cms.blog.show', $post->slug) }}" class="hover:text-blue-600 transition-colors">
                                {{ $post->title }}
                            </a>
                        </h2>

                        @if($post->excerpt)
                            <p class="mb-4 flex-1 text-sm text-gray-600">{{ Str::limit($post->excerpt, 150) }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $post->author?->name ?? 'Unknown' }}</span>
                            <time datetime="{{ $post->published_at?->toDateString() }}">
                                {{ $post->published_at?->format('M d, Y') }}
                            </time>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    @else
        <p class="text-gray-500">No posts found.</p>
    @endif
</div>
@endsection
