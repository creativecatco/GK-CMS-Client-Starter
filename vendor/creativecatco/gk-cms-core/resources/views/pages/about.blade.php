@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="bg-gray-900 text-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                {{ $page->title ?? 'About Us' }}
            </h1>
            <p class="mt-4 text-xl text-gray-300">
                Learn more about who we are and what drives us.
            </p>
        </div>
    </div>
</section>

{{-- Featured Image --}}
@php
    $aboutImg = $page->featured_image ?? null;
    $aboutImgUrl = $aboutImg ? (str_starts_with($aboutImg, 'http') ? $aboutImg : asset('storage/' . $aboutImg)) : null;
@endphp
@if($aboutImgUrl)
<section class="relative -mt-2">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <img
            src="{{ $aboutImgUrl }}"
            alt="{{ $page->title }}"
            class="w-full rounded-xl shadow-2xl object-cover"
            style="max-height: 400px;"
        >
    </div>
</section>
@endif

{{-- Main Content --}}
<section class="py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-16 lg:grid-cols-5">
            {{-- Sidebar --}}
            <div class="lg:col-span-2">
                <div class="sticky top-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Facts</h2>
                    <dl class="space-y-4">
                        <div class="border-l-4 border-indigo-500 pl-4">
                            <dt class="text-sm font-medium text-gray-500 uppercase tracking-wide">Founded</dt>
                            <dd class="text-lg font-semibold text-gray-900">2024</dd>
                        </div>
                        <div class="border-l-4 border-indigo-500 pl-4">
                            <dt class="text-sm font-medium text-gray-500 uppercase tracking-wide">Team Size</dt>
                            <dd class="text-lg font-semibold text-gray-900">Growing</dd>
                        </div>
                        <div class="border-l-4 border-indigo-500 pl-4">
                            <dt class="text-sm font-medium text-gray-500 uppercase tracking-wide">Projects Delivered</dt>
                            <dd class="text-lg font-semibold text-gray-900">50+</dd>
                        </div>
                        <div class="border-l-4 border-indigo-500 pl-4">
                            <dt class="text-sm font-medium text-gray-500 uppercase tracking-wide">Client Satisfaction</dt>
                            <dd class="text-lg font-semibold text-gray-900">100%</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Content --}}
            <div class="lg:col-span-3">
                @if($page && $page->content)
                    <div class="prose prose-lg max-w-none">
                        {!! $page->content !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Values Section --}}
<section class="bg-gray-50 py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Our Values</h2>
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Passion</h3>
                <p class="mt-2 text-gray-600">We love what we do and it shows in every project.</p>
            </div>
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Quality</h3>
                <p class="mt-2 text-gray-600">We never cut corners. Excellence is our standard.</p>
            </div>
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Collaboration</h3>
                <p class="mt-2 text-gray-600">Your success is our success. We work as partners.</p>
            </div>
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Growth</h3>
                <p class="mt-2 text-gray-600">We're always learning, improving, and evolving.</p>
            </div>
        </div>
    </div>
</section>
@endsection
