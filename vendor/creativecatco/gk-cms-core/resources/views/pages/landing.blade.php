@extends('cms-core::layouts.app')

@section('content')
{{-- Hero Section --}}
<section class="relative bg-gradient-to-br from-indigo-900 via-purple-900 to-indigo-800 text-white">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                {{ $page->title ?? 'Welcome' }}
            </h1>
            @if($page && $page->content)
                <div class="mx-auto mt-6 max-w-2xl text-lg text-indigo-100 leading-relaxed">
                    {!! Str::limit(strip_tags($page->content), 200) !!}
                </div>
            @endif
            <div class="mt-10 flex justify-center gap-4">
                <a href="#features" class="rounded-lg bg-white px-8 py-3 text-base font-semibold text-indigo-900 shadow-lg hover:bg-indigo-50 transition-colors">
                    Learn More
                </a>
                <a href="#contact" class="rounded-lg border-2 border-white px-8 py-3 text-base font-semibold text-white hover:bg-white/10 transition-colors">
                    Get in Touch
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section id="features" class="bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900">What We Offer</h2>
            <p class="mt-4 text-lg text-gray-600">Everything you need to succeed online.</p>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-8 text-center hover:shadow-lg transition-shadow">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-7 w-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Web Design</h3>
                <p class="mt-3 text-gray-600">Beautiful, responsive websites that convert visitors into customers.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-8 text-center hover:shadow-lg transition-shadow">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-7 w-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Performance</h3>
                <p class="mt-3 text-gray-600">Lightning-fast load times and optimized user experiences.</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-8 text-center hover:shadow-lg transition-shadow">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-7 w-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900">SEO & Marketing</h3>
                <p class="mt-3 text-gray-600">Data-driven strategies to grow your online presence.</p>
            </div>
        </div>
    </div>
</section>

{{-- Full Content Section --}}
@if($page && $page->content)
<section class="bg-gray-50 py-20">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="prose prose-lg max-w-none">
            {!! $page->content !!}
        </div>
    </div>
</section>
@endif

{{-- CTA Section --}}
<section id="contact" class="bg-indigo-900 py-20">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-white">Ready to Get Started?</h2>
        <p class="mt-4 text-lg text-indigo-200">Let's build something amazing together.</p>
        <a href="/contact" class="mt-8 inline-block rounded-lg bg-white px-10 py-4 text-base font-semibold text-indigo-900 shadow-lg hover:bg-indigo-50 transition-colors">
            Contact Us Today
        </a>
    </div>
</section>
@endsection
