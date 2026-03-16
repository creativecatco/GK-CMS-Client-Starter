{{--
    GKeys CMS - Default Products Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)
    - products (repeater): name, desc, price, image
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'Our Products' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'Premium tools and solutions designed to accelerate your growth.' }}</p>
    </div>
</section>

{{-- Products Grid --}}
<section class="py-20" data-section-bg="products_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $defaultProducts = [
                ['name' => 'Starter Package', 'desc' => 'Perfect for small businesses getting started online. Includes website design and basic SEO.', 'price' => '$499', 'image' => ''],
                ['name' => 'Growth Package', 'desc' => 'For businesses ready to scale. Includes advanced SEO, content marketing, and analytics.', 'price' => '$999', 'image' => ''],
                ['name' => 'Enterprise Package', 'desc' => 'Full-service digital solution for established businesses. Custom development and strategy.', 'price' => '$2,499', 'image' => ''],
                ['name' => 'SEO Toolkit', 'desc' => 'Comprehensive SEO audit, keyword research, and optimization roadmap.', 'price' => '$299', 'image' => ''],
                ['name' => 'Brand Kit', 'desc' => 'Complete brand identity package with logo, colors, typography, and guidelines.', 'price' => '$799', 'image' => ''],
                ['name' => 'Maintenance Plan', 'desc' => 'Monthly website maintenance, updates, security monitoring, and support.', 'price' => '$149/mo', 'image' => ''],
            ];
            $products = $fields['products'] ?? $defaultProducts;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-field="products" data-field-type="repeater"
             data-repeater-fields='[{"key":"name","type":"text","label":"Name"},{"key":"desc","type":"textarea","label":"Description"},{"key":"price","type":"text","label":"Price"},{"key":"image","type":"image","label":"Image"}]'>
            @foreach($products as $index => $product)
                <div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-gray-100" data-repeater-item="{{ $index }}">
                    <div class="aspect-square bg-gray-100">
                        <img src="{{ $product['image'] ?? 'https://placehold.co/400x400/e2e8f0/94a3b8?text=Product+' . ($index + 1) }}"
                             alt="{{ $product['name'] ?? 'Product' }}" class="w-full h-full object-cover"
                             data-field="products.{{ $index }}.image" data-field-type="image">
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-semibold" data-field="products.{{ $index }}.name" data-field-type="text">{{ $product['name'] ?? 'Product Name' }}</h3>
                            <span class="text-lg font-bold whitespace-nowrap ml-2" style="color: var(--color-primary)" data-field="products.{{ $index }}.price" data-field-type="text">{{ $product['price'] ?? '$0' }}</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4" data-field="products.{{ $index }}.desc" data-field-type="textarea">{{ $product['desc'] ?? 'Product description.' }}</p>
                        <a href="/contact" class="inline-block w-full text-center px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
                           style="background-color: var(--color-secondary); color: white">
                            Learn More
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
