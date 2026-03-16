{{--
    GKeys CMS - Products Archive Template
    Displays a grid of published products with pricing.
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">Products</h1>
        <p class="text-xl text-gray-300">Browse our collection of products and services.</p>
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

{{-- Products Grid --}}
<section class="py-16" style="background-color: var(--color-bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    @php
                        $prodImg = $product->featured_image ?? null;
                        $prodImgUrl = $prodImg ? (str_starts_with($prodImg, 'http') ? $prodImg : asset('storage/' . $prodImg)) : null;
                    @endphp
                    <article class="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all">
                        <a href="/products/{{ $product->slug }}" class="block relative overflow-hidden">
                            @if($prodImgUrl)
                                <img src="{{ $prodImgUrl }}"
                                     alt="{{ $product->title }}"
                                     class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full aspect-square flex items-center justify-center bg-gray-100">
                                    <svg class="w-16 h-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                            @endif
                            @if($product->on_sale)
                                <span class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">SALE</span>
                            @endif
                        </a>
                        <div class="p-5">
                            @if($product->categories->count() > 0)
                                <p class="text-xs text-gray-400 mb-1">{{ $product->categories->pluck('name')->join(', ') }}</p>
                            @endif
                            <h2 class="font-semibold mb-2">
                                <a href="/products/{{ $product->slug }}" class="hover:underline" style="color: var(--color-text)">{{ $product->title }}</a>
                            </h2>
                            @if($product->excerpt)
                                <p class="text-gray-500 text-sm mb-3">{{ Str::limit($product->excerpt, 80) }}</p>
                            @endif
                            <div class="flex items-center gap-2">
                                @if($product->on_sale)
                                    <span class="text-lg font-bold" style="color: var(--color-secondary)">${{ $product->sale_price }}</span>
                                    <span class="text-sm text-gray-400 line-through">${{ $product->price }}</span>
                                @elseif($product->price)
                                    <span class="text-lg font-bold" style="color: var(--color-secondary)">${{ $product->display_price }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($products->hasPages())
                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <p class="text-gray-500 text-lg">No products available yet. Check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
