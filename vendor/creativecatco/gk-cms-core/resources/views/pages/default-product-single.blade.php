{{--
    GKeys CMS - Single Product Template
--}}
@extends('cms-core::layouts.app')

@section('content')
<section class="py-16" style="background-color: var(--color-bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {{-- Product Images --}}
            @php
                $prodSingleImg = $product->featured_image ?? null;
                $prodSingleImgUrl = $prodSingleImg ? (str_starts_with($prodSingleImg, 'http') ? $prodSingleImg : asset('storage/' . $prodSingleImg)) : null;
            @endphp
            <div>
                @if($prodSingleImgUrl)
                    <img src="{{ $prodSingleImgUrl }}" alt="{{ $product->title }}"
                         class="w-full rounded-xl shadow-sm aspect-square object-cover mb-4">
                @else
                    <div class="w-full aspect-square rounded-xl flex items-center justify-center bg-gray-100">
                        <svg class="w-24 h-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                @endif

                {{-- Gallery --}}
                @if($product->gallery && count($product->gallery) > 0)
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($product->gallery as $image)
                            <img src="{{ str_starts_with($image, 'http') ? $image : asset('storage/' . $image) }}"
                                 alt="{{ $product->title }}"
                                 class="w-full aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity">
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div>
                @if($product->categories->count() > 0)
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($product->categories as $cat)
                            <span class="text-xs font-medium px-2 py-1 rounded-full"
                                  style="background-color: color-mix(in srgb, var(--color-primary) 15%, white); color: var(--color-secondary)">
                                {{ $cat->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <h1 class="text-3xl md:text-4xl font-extrabold mb-4">{{ $product->title }}</h1>

                {{-- Price --}}
                <div class="flex items-center gap-3 mb-6">
                    @if($product->on_sale)
                        <span class="text-3xl font-bold" style="color: var(--color-secondary)">${{ $product->sale_price }}</span>
                        <span class="text-xl text-gray-400 line-through">${{ $product->price }}</span>
                        @php
                            $discount = round(($product->price - $product->sale_price) / $product->price * 100);
                        @endphp
                        <span class="text-sm font-medium bg-red-100 text-red-600 px-2 py-1 rounded">-{{ $discount }}%</span>
                    @elseif($product->price)
                        <span class="text-3xl font-bold" style="color: var(--color-secondary)">${{ $product->display_price }}</span>
                    @endif
                </div>

                @if($product->sku)
                    <p class="text-sm text-gray-400 mb-4">SKU: {{ $product->sku }}</p>
                @endif

                @if($product->excerpt)
                    <p class="text-gray-600 text-lg mb-6">{{ $product->excerpt }}</p>
                @endif

                {{-- CTA Button --}}
                @if($product->product_url)
                    <a href="{{ $product->product_url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center px-8 py-4 rounded-lg text-lg font-bold transition-all hover:scale-105 hover:shadow-lg mb-8"
                       style="background-color: var(--color-primary); color: var(--color-secondary)">
                        Buy Now &rarr;
                    </a>
                @endif

                {{-- Description --}}
                @if($product->content)
                    <div class="border-t pt-6 mt-6">
                        <h2 class="text-xl font-bold mb-4">Description</h2>
                        <div class="prose prose-lg max-w-none">
                            {!! $product->content !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Back Link --}}
        <div class="border-t pt-8 mt-12">
            <a href="/products" class="inline-flex items-center gap-2 font-semibold hover:underline" style="color: var(--color-secondary)">
                &larr; Back to Products
            </a>
        </div>
    </div>
</section>

{{-- Related Products --}}
@if(isset($related) && $related->count() > 0)
<section class="py-16 border-t" style="background-color: #f6f1e6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-8 text-center">You May Also Like</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($related as $item)
                @php
                    $relProdImg = $item->featured_image ?? null;
                    $relProdImgUrl = $relProdImg ? (str_starts_with($relProdImg, 'http') ? $relProdImg : asset('storage/' . $relProdImg)) : null;
                @endphp
                <article class="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                    <a href="/products/{{ $item->slug }}" class="block">
                        @if($relProdImgUrl)
                            <img src="{{ $relProdImgUrl }}" alt="{{ $item->title }}"
                                 class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full aspect-square flex items-center justify-center bg-gray-100">
                                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                        @endif
                    </a>
                    <div class="p-4">
                        <h3 class="font-semibold text-sm mb-1"><a href="/products/{{ $item->slug }}" class="hover:underline">{{ $item->title }}</a></h3>
                        @if($item->display_price)
                            <span class="font-bold text-sm" style="color: var(--color-secondary)">${{ $item->display_price }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
