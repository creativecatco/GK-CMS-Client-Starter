{{--
    GKeys CMS - Default Contact Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)
    - address (textarea), email (text), phone (text)
    - hours (textarea): Business hours
    - map_embed (video): Google Maps embed URL
--}}
@extends('cms-core::layouts.app')

@php
    // Pull contact info from Settings (try contact_* first, fall back to company_*)
    $settingsEmail = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_email', '') ?: \CreativeCatCo\GkCmsCore\Models\Setting::get('company_email', '');
    $settingsPhone = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_phone', '') ?: \CreativeCatCo\GkCmsCore\Models\Setting::get('company_phone', '');
    $settingsAddress = \CreativeCatCo\GkCmsCore\Models\Setting::get('contact_address', '') ?: \CreativeCatCo\GkCmsCore\Models\Setting::get('company_address', '');

    // Use page field if it was explicitly customized, otherwise use settings value
    $displayEmail = (!empty($fields['email']) && $fields['email'] !== 'hello@example.com') ? $fields['email'] : ($settingsEmail ?: 'hello@example.com');
    $displayPhone = (!empty($fields['phone']) && $fields['phone'] !== '(555) 123-4567') ? $fields['phone'] : ($settingsPhone ?: '(555) 123-4567');
    $displayAddress = (!empty($fields['address']) && $fields['address'] !== '123 Main Street, Suite 100, City, State 12345') ? $fields['address'] : ($settingsAddress ?: '123 Main Street, Suite 100, City, State 12345');
@endphp

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'Contact Us' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'We\'d love to hear from you. Get in touch and let\'s discuss your project.' }}</p>
    </div>
</section>

{{-- Contact Content --}}
<section class="py-20" data-section-bg="contact_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            {{-- Contact Form --}}
            <div>
                <h2 class="text-2xl font-bold mb-6" data-field="form_heading" data-field-type="text">{{ $fields['form_heading'] ?? 'Send Us a Message' }}</h2>
                <form class="space-y-4" method="POST" action="/api/cms/contact">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:border-transparent" style="focus:ring-color: var(--color-primary)" placeholder="Your name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:border-transparent" placeholder="your@email.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:border-transparent" placeholder="(555) 123-4567">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="5" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:border-transparent" placeholder="Tell us about your project..."></textarea>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 rounded-lg font-semibold text-lg transition-transform hover:scale-105"
                        style="background-color: var(--color-primary); color: var(--color-secondary)">
                        Send Message
                    </button>
                </form>
            </div>

            {{-- Contact Info --}}
            <div class="space-y-8">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Address</h3>
                    <p class="text-gray-600" data-field="address" data-field-type="textarea">{{ $displayAddress }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Email</h3>
                    <p class="text-gray-600" data-field="email" data-field-type="text">{{ $displayEmail }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Phone</h3>
                    <p class="text-gray-600" data-field="phone" data-field-type="text">{{ $displayPhone }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Business Hours</h3>
                    <p class="text-gray-600" data-field="hours" data-field-type="textarea">{{ $fields['hours'] ?? "Monday - Friday: 9am - 5pm\nSaturday - Sunday: Closed" }}</p>
                </div>

                {{-- Map --}}
                <div class="rounded-xl overflow-hidden shadow-lg h-64 bg-gray-200">
                    <iframe src="{{ $fields['map_embed'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387193.30596698663!2d-74.25986548248684!3d40.69714941932609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY!5e0!3m2!1sen!2sus!4v1' }}"
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                        data-field="map_embed" data-field-type="video"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
