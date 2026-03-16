@php
    $heading = $block['data']['heading'] ?? '';
    $subheading = $block['data']['subheading'] ?? '';
    $showInfo = $block['data']['show_info_sidebar'] ?? true;
    $showSocial = $block['data']['show_social_links'] ?? true;
    $submitText = $block['data']['submit_text'] ?? 'Send Message';
    $successMessage = $block['data']['success_message'] ?? 'Thank you! We\'ll be in touch soon.';
    $bgColor = $block['data']['bg_color'] ?? 'white';

    $bgClass = match($bgColor) {
        'gray' => 'bg-gray-50',
        default => 'bg-white',
    };

    $email = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_email', '');
    $phone = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_phone', '');
    $address = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_address', '');
    $facebook = \CreativeCatCo\GkCmsCore\Models\Setting::get('social_facebook', '');
    $instagram = \CreativeCatCo\GkCmsCore\Models\Setting::get('social_instagram', '');
    $linkedin = \CreativeCatCo\GkCmsCore\Models\Setting::get('social_linkedin', '');
    $twitter = \CreativeCatCo\GkCmsCore\Models\Setting::get('social_twitter', '');
@endphp

<section class="{{ $bgClass }} py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6">
        @if($heading || $subheading)
            <div class="text-center mb-12">
                @if($heading)
                    <h2 class="text-3xl md:text-4xl font-bold mb-3 text-gray-900">{{ $heading }}</h2>
                @endif
                @if($subheading)
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        <div class="grid {{ $showInfo ? 'md:grid-cols-3' : 'md:grid-cols-1 max-w-2xl mx-auto' }} gap-12">
            {{-- Contact Form --}}
            <div class="{{ $showInfo ? 'md:col-span-2' : '' }}">
                <form method="POST" action="#" class="space-y-6" x-data="{ submitted: false }" @submit.prevent="submitted = true">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" id="first_name" name="first_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"></textarea>
                    </div>
                    <button type="submit" x-show="!submitted" class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                        {{ $submitText }}
                    </button>
                    <div x-show="submitted" x-cloak class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 font-medium">
                        {{ $successMessage }}
                    </div>
                </form>
            </div>

            {{-- Contact Info Sidebar --}}
            @if($showInfo)
                <div class="space-y-8">
                    @if($email)
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Email</h4>
                                <a href="mailto:{{ $email }}" class="text-blue-600 hover:underline">{{ $email }}</a>
                            </div>
                        </div>
                    @endif

                    @if($phone)
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Phone</h4>
                                <a href="tel:{{ $phone }}" class="text-blue-600 hover:underline">{{ $phone }}</a>
                            </div>
                        </div>
                    @endif

                    @if($address)
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Address</h4>
                                <p class="text-gray-600">{{ $address }}</p>
                            </div>
                        </div>
                    @endif

                    @if($showSocial && ($facebook || $instagram || $linkedin || $twitter))
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">Follow Us</h4>
                            <div class="flex gap-3">
                                @if($facebook)
                                    <a href="{{ $facebook }}" target="_blank" rel="noopener" class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-blue-100 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                @endif
                                @if($instagram)
                                    <a href="{{ $instagram }}" target="_blank" rel="noopener" class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-pink-100 hover:text-pink-600 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    </a>
                                @endif
                                @if($linkedin)
                                    <a href="{{ $linkedin }}" target="_blank" rel="noopener" class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                    </a>
                                @endif
                                @if($twitter)
                                    <a href="{{ $twitter }}" target="_blank" rel="noopener" class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-sky-100 hover:text-sky-500 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
