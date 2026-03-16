{{--
    GKeys CMS - Default Services Page Template

    Fields:
    - page_heading (text), page_subheading (textarea)
    - services_items (repeater): title, desc, icon
    - cta_heading (text), cta_text (textarea), cta_button (text)
--}}
@extends('cms-core::layouts.app')

@section('content')
{{-- Page Header --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="header_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4" data-field="page_heading" data-field-type="text">{{ $fields['page_heading'] ?? 'Our Services' }}</h1>
        <p class="text-xl text-gray-300" data-field="page_subheading" data-field-type="textarea">{{ $fields['page_subheading'] ?? 'Comprehensive digital solutions to help your business thrive online.' }}</p>
    </div>
</section>

{{-- Services Grid --}}
<section class="py-20" data-section-bg="services_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $defaultServices = [
                ['title' => 'Web Design', 'desc' => 'Custom, responsive websites designed to convert visitors into customers.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'],
                ['title' => 'SEO Optimization', 'desc' => 'Boost your search rankings and drive organic traffic to your site.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'],
                ['title' => 'Content Marketing', 'desc' => 'Engaging content strategies that build authority and attract leads.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'],
                ['title' => 'Social Media', 'desc' => 'Strategic social media management to grow your online presence.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>'],
                ['title' => 'E-Commerce', 'desc' => 'Online stores built for seamless shopping experiences and growth.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>'],
                ['title' => 'Brand Strategy', 'desc' => 'Build a cohesive brand identity that resonates with your audience.', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
            ];
            $servicesItems = $fields['services_items'] ?? $defaultServices;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-field="services_items" data-field-type="repeater"
             data-repeater-fields='[{"key":"title","type":"text","label":"Title"},{"key":"desc","type":"textarea","label":"Description"},{"key":"icon","type":"icon","label":"Icon"}]'>
            @foreach($servicesItems as $index => $service)
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow" data-repeater-item="{{ $index }}">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4" style="background-color: color-mix(in srgb, var(--color-primary) 20%, white); color: var(--color-secondary)">
                        <span data-field="services_items.{{ $index }}.icon" data-field-type="icon">{!! $service['icon'] ?? '' !!}</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" data-field="services_items.{{ $index }}.title" data-field-type="text">{{ $service['title'] ?? 'Service' }}</h3>
                    <p class="text-gray-600" data-field="services_items.{{ $index }}.desc" data-field-type="textarea">{{ $service['desc'] ?? 'Service description.' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="cta_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-white mb-4" data-field="cta_heading" data-field-type="text">{{ $fields['cta_heading'] ?? 'Ready to Transform Your Business?' }}</h2>
        <p class="text-gray-300 text-lg mb-8" data-field="cta_text" data-field-type="textarea">{{ $fields['cta_text'] ?? 'Let\'s discuss how our services can help you achieve your goals.' }}</p>
        <a href="/contact" class="inline-flex items-center px-8 py-4 rounded-lg text-lg font-bold transition-transform hover:scale-105"
           style="background-color: var(--color-primary); color: var(--color-secondary)"
           data-field="cta_button" data-field-type="text">{{ $fields['cta_button'] ?? 'Get a Free Quote' }}</a>
    </div>
</section>
@endsection
