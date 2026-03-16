@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="bg-gradient-to-r from-gray-900 to-gray-800 text-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
            {{ $page->title ?? 'Contact Us' }}
        </h1>
        <p class="mt-4 text-xl text-gray-300">
            We'd love to hear from you. Get in touch and let's start a conversation.
        </p>
    </div>
</section>

{{-- Contact Content --}}
<section class="py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-16 lg:grid-cols-3">

            {{-- Contact Info Sidebar --}}
            <div class="lg:col-span-1">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Get In Touch</h2>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Email</h3>
                            @php $email = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_email', 'hello@example.com'); @endphp
                            <a href="mailto:{{ $email }}" class="text-indigo-600 hover:text-indigo-800">{{ $email }}</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Phone</h3>
                            @php $phone = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_phone', '(555) 123-4567'); @endphp
                            <a href="tel:{{ $phone }}" class="text-indigo-600 hover:text-indigo-800">{{ $phone }}</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Address</h3>
                            @php $address = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_address', '123 Main Street'); @endphp
                            <p class="text-gray-600">{{ $address }}</p>
                        </div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="mt-10">
                    <h3 class="font-semibold text-gray-900 mb-4">Follow Us</h3>
                    <div class="flex gap-4">
                        @php $facebook = \CreativeCatCo\GkCmsCore\Models\Setting::get('facebook_url'); @endphp
                        @php $twitter = \CreativeCatCo\GkCmsCore\Models\Setting::get('twitter_url'); @endphp
                        @php $instagram = \CreativeCatCo\GkCmsCore\Models\Setting::get('instagram_url'); @endphp
                        @php $linkedin = \CreativeCatCo\GkCmsCore\Models\Setting::get('linkedin_url'); @endphp

                        @if($facebook)
                        <a href="{{ $facebook }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if($twitter)
                        <a href="{{ $twitter }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        @endif
                        @if($instagram)
                        <a href="{{ $instagram }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        @endif
                        @if($linkedin)
                        <a href="{{ $linkedin }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contact Form / Content --}}
            <div class="lg:col-span-2">
                @if($page && $page->content)
                    <div class="prose prose-lg max-w-none mb-12">
                        {!! $page->content !!}
                    </div>
                @endif

                {{-- Contact Form --}}
                <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Send Us a Message</h2>
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" id="name" required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors"
                                    placeholder="John Doe">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" id="email" required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors"
                                    placeholder="john@example.com">
                            </div>
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <input type="text" name="subject" id="subject"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors"
                                placeholder="How can we help?">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea name="message" id="message" rows="5" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors"
                                placeholder="Tell us about your project..."></textarea>
                        </div>
                        <button type="submit"
                            class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
