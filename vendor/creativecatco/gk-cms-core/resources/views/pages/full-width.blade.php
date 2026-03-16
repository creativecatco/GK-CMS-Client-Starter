@extends('cms-core::layouts.app')

@section('content')
{{-- Full Width Template --}}
{{-- This template renders content edge-to-edge with no container constraints. --}}
{{-- Ideal for custom HTML sections, hero banners, or fully designed pages. --}}

@php
    $fwImg = $page->featured_image ?? null;
    $fwImgUrl = $fwImg ? (str_starts_with($fwImg, 'http') ? $fwImg : asset('storage/' . $fwImg)) : null;
@endphp
@if($fwImgUrl)
<section class="relative">
    <img
        src="{{ $fwImgUrl }}"
        alt="{{ $page->title }}"
        class="w-full object-cover"
        style="max-height: 500px;"
    >
    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
        <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl text-center px-4">
            {{ $page->title }}
        </h1>
    </div>
</section>
@else
<section class="bg-gray-100 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
            {{ $page->title ?? '' }}
        </h1>
    </div>
</section>
@endif

@if($page && $page->content)
<div class="cms-full-width-content">
    {!! $page->content !!}
</div>
@endif
@endsection
