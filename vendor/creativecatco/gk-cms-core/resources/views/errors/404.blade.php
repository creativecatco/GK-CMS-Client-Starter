@extends('cms-core::layouts.app')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-24 text-center sm:px-6 lg:px-8">
    <h1 class="text-6xl font-bold text-gray-900">404</h1>
    <p class="mt-4 text-xl text-gray-600">Page not found.</p>
    <p class="mt-2 text-gray-500">The page you are looking for does not exist or has been moved.</p>
    <a href="{{ url('/') }}" class="mt-8 inline-block rounded-md bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
        Go Home
    </a>
</div>
@endsection
