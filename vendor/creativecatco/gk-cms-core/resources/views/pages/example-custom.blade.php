{{--
    EXAMPLE: AI-Generated Custom Template with Editable Fields v3
    
    This file demonstrates the full pattern for creating pages with editable fields.
    The AI writes the full HTML/Blade, and marks editable regions with data attributes.
    
    FIELD TYPES & CONVENTIONS:
    ─────────────────────────
    TEXT:         data-field="key" data-field-type="text" data-field-label="Label" data-field-group="Group"
    TEXTAREA:     data-field="key" data-field-type="textarea" data-field-label="Label" data-field-group="Group"
    RICHTEXT:     data-field="key" data-field-type="richtext" data-field-label="Label" data-field-group="Group"
    IMAGE (img):  data-field="key" data-field-type="image" data-field-label="Label" data-field-group="Group"
    IMAGE (bg):   data-field="key" data-field-type="image" data-field-label="Label" data-field-group="Group"
                  (on element with background-image style)
    BUTTON:       data-field="key" data-field-type="button" data-field-label="Label" data-field-group="Group"
                  data-button-style="primary" (style name from theme)
    BUTTON GROUP: data-field="key" data-field-type="button_group" data-field-label="Label" data-field-group="Group"
                  (children have data-button-index="0", data-button-index="1", etc.)
    GALLERY:      data-field="key" data-field-type="gallery" data-field-label="Label" data-field-group="Group"
                  data-gallery-layout="grid|slider" (container with data-gallery-grid child)
                  (children <img> tags have data-gallery-item attribute)
    COLOR:        data-field="key" data-field-type="color" data-field-label="Label" data-field-group="Group"
                  data-color-value="#hex" data-color-target="background-color|color"
    VIDEO:        data-field="key" data-field-type="video" data-field-label="Label" data-field-group="Group"
                  data-video-url="url" data-video-type="youtube|vimeo|upload|direct"
    ICON:         data-field="key" data-field-type="icon" data-field-label="Label" data-field-group="Group"
                  data-icon-name="icon-name" (contains an SVG child)
    REPEATER:     data-field="key" data-field-type="repeater" data-field-label="Label" data-field-group="Group"
                  (children have data-repeater-item, sub-fields have data-repeater-sub="subkey"
                   data-repeater-sub-type="text|textarea|image" data-repeater-default="Default text")
    SECTION BG:   data-section-bg="key" data-field-label="Label" data-bg-mode="cover|parallax|static|repeat"
    
    VALUES:
    Use {{ $fields['key'] ?? 'Default Value' }} for text
    Use {!! $fields['key'] ?? '<p>Default</p>' !!} for richtext
    Use {{ asset('storage/' . ($fields['key'] ?? '')) }} for images
    Use {{ $fields['key']['text'] ?? 'Click' }} for button text
    Use {{ $fields['key']['link'] ?? '#' }} for button links
    Gallery stores: [{ "src": "path/to/img.jpg", "alt": "Caption" }, ...]
    Repeater stores: [{ "subkey1": "value", "subkey2": "value" }, ...]
    Color stores: "#hexcolor"
    Video stores: { "url": "...", "type": "youtube|vimeo|upload|direct" }
    Icon stores: "icon-name"
--}}

@extends('cms-core::layouts.app')

@section('content')

{{-- Theme Button Styles Definition (AI sets these per-project) --}}
<div style="display:none;" data-button-styles='{
    "primary": { "label": "Primary", "classes": "px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors text-lg" },
    "secondary": { "label": "Secondary", "classes": "px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition-colors text-lg border border-white/30" },
    "accent": { "label": "Accent", "classes": "px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors text-lg" }
}'></div>

{{-- ═══ HERO SECTION ═══ --}}
<section class="relative min-h-[80vh] flex items-center justify-center overflow-hidden"
         data-section-bg="hero_bg" data-field-label="Hero Background" data-bg-mode="cover"
         style="background-image: url('{{ asset('storage/' . (is_array($fields['hero_bg'] ?? null) ? ($fields['hero_bg']['image'] ?? '') : ($fields['hero_bg_image'] ?? ''))) }}'); background-size: cover; background-position: center;">
    
    {{-- Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/80 to-blue-900/60 pointer-events-none" style="z-index:0;"></div>
    
    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center text-white">
        <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6"
            data-field="hero_headline" data-field-type="text" data-field-label="Hero Headline" data-field-group="Hero">
            {{ $fields['hero_headline'] ?? 'We Build Digital Experiences That Matter' }}
        </h1>
        <p class="text-xl md:text-2xl text-white/80 mb-10 max-w-2xl mx-auto"
           data-field="hero_subheadline" data-field-type="textarea" data-field-label="Hero Subheadline" data-field-group="Hero">
            {{ $fields['hero_subheadline'] ?? 'We create stunning digital experiences that drive results for your business.' }}
        </p>
        
        {{-- Button Group --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center"
             data-field="hero_buttons" data-field-type="button_group" data-field-label="Hero Buttons" data-field-group="Hero">
            @php
                $heroButtons = $fields['hero_buttons'] ?? [
                    ['text' => 'Get Started', 'link' => '/contact', 'style' => 'primary', 'visible' => true],
                    ['text' => 'Learn More', 'link' => '/about', 'style' => 'secondary', 'visible' => true],
                ];
            @endphp
            @foreach($heroButtons as $i => $btn)
                @if($btn['visible'] ?? true)
                <a href="{{ $btn['link'] ?? '#' }}"
                   data-button-index="{{ $i }}"
                   data-button-style="{{ $btn['style'] ?? 'primary' }}"
                   class="{{ $btn['style'] === 'secondary' ? 'px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition-colors text-lg border border-white/30' : ($btn['style'] === 'accent' ? 'px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors text-lg' : 'px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors text-lg') }}">
                    {{ $btn['text'] ?? 'Button' }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ SERVICES SECTION (with Icon fields) ═══ --}}
<section class="py-20" data-section-bg="services_bg" data-field-label="Services Background" data-bg-mode="cover"
         style="background-color: #ffffff;">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4"
                data-field="services_headline" data-field-type="text" data-field-label="Services Headline" data-field-group="Services">
                {{ $fields['services_headline'] ?? 'What We Do' }}
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto"
               data-field="services_description" data-field-type="textarea" data-field-label="Services Description" data-field-group="Services">
                {{ $fields['services_description'] ?? 'We offer a full range of digital services to help your business grow and thrive online.' }}
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            {{-- Service Card 1 --}}
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-6"
                     data-field="service1_icon" data-field-type="icon" data-field-label="Service 1 Icon" data-field-group="Services"
                     data-icon-name="monitor">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3"
                    data-field="service1_title" data-field-type="text" data-field-label="Service 1 Title" data-field-group="Services">
                    {{ $fields['service1_title'] ?? 'Web Design' }}
                </h3>
                <p class="text-gray-600"
                   data-field="service1_description" data-field-type="textarea" data-field-label="Service 1 Description" data-field-group="Services">
                    {{ $fields['service1_description'] ?? 'Beautiful, responsive websites that convert visitors into customers.' }}
                </p>
            </div>

            {{-- Service Card 2 --}}
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-6"
                     data-field="service2_icon" data-field-type="icon" data-field-label="Service 2 Icon" data-field-group="Services"
                     data-icon-name="trending-up">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3"
                    data-field="service2_title" data-field-type="text" data-field-label="Service 2 Title" data-field-group="Services">
                    {{ $fields['service2_title'] ?? 'SEO & Marketing' }}
                </h3>
                <p class="text-gray-600"
                   data-field="service2_description" data-field-type="textarea" data-field-label="Service 2 Description" data-field-group="Services">
                    {{ $fields['service2_description'] ?? 'Data-driven strategies to increase your visibility and drive organic growth.' }}
                </p>
            </div>

            {{-- Service Card 3 --}}
            <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center mb-6"
                     data-field="service3_icon" data-field-type="icon" data-field-label="Service 3 Icon" data-field-group="Services"
                     data-icon-name="smartphone">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3"
                    data-field="service3_title" data-field-type="text" data-field-label="Service 3 Title" data-field-group="Services">
                    {{ $fields['service3_title'] ?? 'App Development' }}
                </h3>
                <p class="text-gray-600"
                   data-field="service3_description" data-field-type="textarea" data-field-label="Service 3 Description" data-field-group="Services">
                    {{ $fields['service3_description'] ?? 'Custom mobile and web applications tailored to your unique business needs.' }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ ABOUT / IMAGE+TEXT SECTION ═══ --}}
<section class="py-20" data-section-bg="about_bg" data-field-label="About Background" data-bg-mode="cover"
         style="background-color: #f8fafc;">
    <div class="max-w-6xl mx-auto px-6">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div class="relative">
                <img src="{{ asset('storage/' . ($fields['about_image'] ?? '')) }}"
                     alt="About us"
                     class="rounded-xl shadow-lg w-full"
                     onerror="this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=About+Image'"
                     data-field="about_image" data-field-type="image" data-field-label="About Image" data-field-group="About">
            </div>
            <div>
                <h2 class="text-4xl font-bold text-gray-900 mb-6"
                    data-field="about_headline" data-field-type="text" data-field-label="About Headline" data-field-group="About">
                    {{ $fields['about_headline'] ?? 'Why Choose Us' }}
                </h2>
                <div class="text-gray-600 space-y-4 text-lg"
                     data-field="about_description" data-field-type="richtext" data-field-label="About Description" data-field-group="About">
                    {!! $fields['about_description'] ?? '<p>With over a decade of experience, we bring a unique blend of creativity and technical expertise to every project.</p><p>Our team is passionate about delivering results that exceed expectations.</p>' !!}
                </div>
                
                @php
                    $aboutBtn = $fields['about_button'] ?? ['text' => 'Learn More About Us', 'link' => '/about', 'style' => 'primary', 'visible' => true];
                @endphp
                @if($aboutBtn['visible'] ?? true)
                <a href="{{ $aboutBtn['link'] ?? '/about' }}"
                   class="inline-block mt-8 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
                   data-field="about_button" data-field-type="button" data-field-label="About Button" data-field-group="About"
                   data-button-style="{{ $aboutBtn['style'] ?? 'primary' }}">
                    {{ $aboutBtn['text'] ?? 'Learn More About Us' }}
                </a>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ═══ VIDEO SECTION ═══ --}}
<section class="py-20" data-section-bg="video_bg" data-field-label="Video Background" data-bg-mode="cover"
         style="background-color: #0f172a;">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-white mb-4"
                data-field="video_headline" data-field-type="text" data-field-label="Video Headline" data-field-group="Video">
                {{ $fields['video_headline'] ?? 'See Our Work in Action' }}
            </h2>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto"
               data-field="video_description" data-field-type="textarea" data-field-label="Video Description" data-field-group="Video">
                {{ $fields['video_description'] ?? 'Watch how we transform ideas into digital reality.' }}
            </p>
        </div>

        {{-- Video Embed Field --}}
        @php
            $videoData = $fields['video_embed'] ?? ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'type' => 'youtube'];
            $videoUrl = is_array($videoData) ? ($videoData['url'] ?? '') : $videoData;
            $videoType = is_array($videoData) ? ($videoData['type'] ?? 'youtube') : 'youtube';
        @endphp
        <div class="relative rounded-xl overflow-hidden shadow-2xl"
             data-field="video_embed" data-field-type="video" data-field-label="Featured Video" data-field-group="Video"
             data-video-url="{{ $videoUrl }}" data-video-type="{{ $videoType }}">
            @if($videoType === 'youtube')
                @php
                    preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $ytMatch);
                    $ytId = $ytMatch[1] ?? '';
                @endphp
                <iframe src="https://www.youtube.com/embed/{{ $ytId }}"
                        style="width:100%;aspect-ratio:16/9;border:none;"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            @elseif($videoType === 'vimeo')
                @php
                    preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $videoUrl, $vimeoMatch);
                    $vimeoId = $vimeoMatch[1] ?? '';
                @endphp
                <iframe src="https://player.vimeo.com/video/{{ $vimeoId }}"
                        style="width:100%;aspect-ratio:16/9;border:none;"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen></iframe>
            @elseif($videoType === 'upload' || $videoType === 'direct')
                <video src="{{ $videoUrl }}" controls style="width:100%;aspect-ratio:16/9;"></video>
            @else
                <div class="flex items-center justify-center bg-slate-800" style="aspect-ratio:16/9;">
                    <p class="text-slate-400 text-lg">Click "Edit Video" to add a video</p>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ═══ STATS SECTION (with Color fields) ═══ --}}
<section class="py-16" data-section-bg="stats_bg" data-field-label="Stats Background" data-bg-mode="cover"
         style="background-color: {{ $fields['stats_bg_color'] ?? '#1e293b' }};">
    <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
            <div>
                <div class="text-4xl font-extrabold mb-2"
                     data-field="stat1_number" data-field-type="text" data-field-label="Stat 1 Number" data-field-group="Stats">
                    {{ $fields['stat1_number'] ?? '150+' }}
                </div>
                <div class="text-slate-400"
                     data-field="stat1_label" data-field-type="text" data-field-label="Stat 1 Label" data-field-group="Stats">
                    {{ $fields['stat1_label'] ?? 'Projects Completed' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2"
                     data-field="stat2_number" data-field-type="text" data-field-label="Stat 2 Number" data-field-group="Stats">
                    {{ $fields['stat2_number'] ?? '50+' }}
                </div>
                <div class="text-slate-400"
                     data-field="stat2_label" data-field-type="text" data-field-label="Stat 2 Label" data-field-group="Stats">
                    {{ $fields['stat2_label'] ?? 'Happy Clients' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2"
                     data-field="stat3_number" data-field-type="text" data-field-label="Stat 3 Number" data-field-group="Stats">
                    {{ $fields['stat3_number'] ?? '10+' }}
                </div>
                <div class="text-slate-400"
                     data-field="stat3_label" data-field-type="text" data-field-label="Stat 3 Label" data-field-group="Stats">
                    {{ $fields['stat3_label'] ?? 'Years Experience' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2"
                     data-field="stat4_number" data-field-type="text" data-field-label="Stat 4 Number" data-field-group="Stats">
                    {{ $fields['stat4_number'] ?? '99%' }}
                </div>
                <div class="text-slate-400"
                     data-field="stat4_label" data-field-type="text" data-field-label="Stat 4 Label" data-field-group="Stats">
                    {{ $fields['stat4_label'] ?? 'Client Satisfaction' }}
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FAQ SECTION (Repeater Demo) ═══ --}}
<section class="py-20" data-section-bg="faq_bg" data-field-label="FAQ Background" data-bg-mode="cover"
         style="background-color: #ffffff;">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4"
                data-field="faq_headline" data-field-type="text" data-field-label="FAQ Headline" data-field-group="FAQ">
                {{ $fields['faq_headline'] ?? 'Frequently Asked Questions' }}
            </h2>
            <p class="text-lg text-gray-600"
               data-field="faq_description" data-field-type="textarea" data-field-label="FAQ Description" data-field-group="FAQ">
                {{ $fields['faq_description'] ?? 'Find answers to common questions about our services.' }}
            </p>
        </div>

        {{-- FAQ Repeater --}}
        @php
            $faqItems = $fields['faq_items'] ?? [
                ['question' => 'What services do you offer?', 'answer' => 'We offer web design, SEO, app development, and digital marketing services tailored to your business needs.'],
                ['question' => 'How long does a typical project take?', 'answer' => 'Most projects are completed within 4-8 weeks, depending on scope and complexity. We provide detailed timelines during our initial consultation.'],
                ['question' => 'Do you offer ongoing support?', 'answer' => 'Yes! We offer maintenance packages that include updates, security monitoring, and priority support to keep your site running smoothly.'],
                ['question' => 'What is your pricing structure?', 'answer' => 'We offer flexible pricing based on project scope. Contact us for a free quote tailored to your specific requirements.'],
            ];
        @endphp

        <div class="space-y-4"
             data-field="faq_items" data-field-type="repeater" data-field-label="FAQ Items" data-field-group="FAQ">
            @foreach($faqItems as $faq)
            <div data-repeater-item class="border border-gray-200 rounded-lg overflow-hidden">
                <button class="w-full text-left px-6 py-4 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center justify-between"
                        onclick="this.parentElement.classList.toggle('faq-open'); this.querySelector('svg').classList.toggle('rotate-180');">
                    <span class="text-lg font-semibold text-gray-900"
                          data-repeater-sub="question" data-repeater-sub-type="text" data-repeater-default="New Question">{{ $faq['question'] ?? 'Question' }}</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="px-6 py-4 text-gray-600 hidden faq-answer">
                    <p data-repeater-sub="answer" data-repeater-sub-type="textarea" data-repeater-default="Answer goes here...">{{ $faq['answer'] ?? 'Answer' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .faq-open .faq-answer { display: block !important; }
</style>

{{-- ═══ TESTIMONIALS SECTION (Repeater with Images) ═══ --}}
<section class="py-20" data-section-bg="testimonials_bg" data-field-label="Testimonials Background" data-bg-mode="cover"
         style="background-color: #f8fafc;">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4"
                data-field="testimonials_headline" data-field-type="text" data-field-label="Testimonials Headline" data-field-group="Testimonials">
                {{ $fields['testimonials_headline'] ?? 'What Our Clients Say' }}
            </h2>
        </div>

        @php
            $testimonials = $fields['testimonial_items'] ?? [
                ['quote' => 'Working with this team was an absolute pleasure. They delivered beyond our expectations and on time.', 'name' => 'Sarah Johnson', 'role' => 'CEO, TechStart Inc.', 'avatar' => ''],
                ['quote' => 'The attention to detail and creative solutions they provided transformed our online presence completely.', 'name' => 'Michael Chen', 'role' => 'Marketing Director, GrowthCo', 'avatar' => ''],
                ['quote' => 'Professional, responsive, and incredibly talented. I highly recommend their services to anyone.', 'name' => 'Emily Rodriguez', 'role' => 'Founder, DesignLab', 'avatar' => ''],
            ];
        @endphp

        <div class="grid md:grid-cols-3 gap-8"
             data-field="testimonial_items" data-field-type="repeater" data-field-label="Testimonials" data-field-group="Testimonials">
            @foreach($testimonials as $t)
            <div data-repeater-item class="bg-white rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-1 mb-4 text-amber-400">
                    @for($s = 0; $s < 5; $s++)
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 mb-6 italic"
                   data-repeater-sub="quote" data-repeater-sub-type="textarea" data-repeater-default="Client testimonial goes here...">"{{ $t['quote'] ?? 'Great service!' }}"</p>
                <div class="flex items-center gap-4">
                    <img src="{{ ($t['avatar'] ?? '') ? asset('storage/' . $t['avatar']) : 'https://placehold.co/48x48/e2e8f0/94a3b8?text=' . urlencode(substr($t['name'] ?? 'A', 0, 1)) }}"
                         alt="{{ $t['name'] ?? 'Client' }}"
                         class="w-12 h-12 rounded-full object-cover"
                         onerror="this.src='https://placehold.co/48x48/e2e8f0/94a3b8?text=A'"
                         data-repeater-sub="avatar" data-repeater-sub-type="image">
                    <div>
                        <div class="font-semibold text-gray-900"
                             data-repeater-sub="name" data-repeater-sub-type="text" data-repeater-default="Client Name">{{ $t['name'] ?? 'Client Name' }}</div>
                        <div class="text-sm text-gray-500"
                             data-repeater-sub="role" data-repeater-sub-type="text" data-repeater-default="Role, Company">{{ $t['role'] ?? 'Role, Company' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ GALLERY SECTION ═══ --}}
<section class="py-20" data-section-bg="gallery_bg" data-field-label="Gallery Background" data-bg-mode="cover"
         style="background-color: #ffffff;">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4"
                data-field="gallery_headline" data-field-type="text" data-field-label="Gallery Headline" data-field-group="Gallery">
                {{ $fields['gallery_headline'] ?? 'Our Work' }}
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto"
               data-field="gallery_description" data-field-type="textarea" data-field-label="Gallery Description" data-field-group="Gallery">
                {{ $fields['gallery_description'] ?? 'A showcase of our recent projects and creative work.' }}
            </p>
        </div>

        @php
            $galleryImages = $fields['gallery_images'] ?? [
                ['src' => '', 'alt' => 'Project 1'],
                ['src' => '', 'alt' => 'Project 2'],
                ['src' => '', 'alt' => 'Project 3'],
                ['src' => '', 'alt' => 'Project 4'],
                ['src' => '', 'alt' => 'Project 5'],
                ['src' => '', 'alt' => 'Project 6'],
            ];
            $galleryLayout = $fields['gallery_layout'] ?? 'grid';
        @endphp

        <div data-field="gallery_images" data-field-type="gallery" data-field-label="Gallery Images" data-field-group="Gallery"
             data-gallery-layout="{{ $galleryLayout }}">

            @if($galleryLayout === 'slider')
            <div class="relative overflow-hidden" data-gallery-grid>
                <div class="flex gap-6 overflow-x-auto snap-x snap-mandatory pb-4 scrollbar-hide" id="gallery-slider">
                    @foreach($galleryImages as $img)
                    <img src="{{ $img['src'] ? asset('storage/' . $img['src']) : 'https://placehold.co/600x400/e2e8f0/94a3b8?text=' . urlencode($img['alt'] ?? 'Gallery Image') }}"
                         alt="{{ $img['alt'] ?? 'Gallery image' }}"
                         data-gallery-item
                         class="w-80 h-64 object-cover rounded-lg flex-shrink-0 snap-center"
                         onerror="this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=Gallery+Image'">
                    @endforeach
                </div>
                <button onclick="document.getElementById('gallery-slider').scrollBy({left:-320,behavior:'smooth'})"
                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-20">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button onclick="document.getElementById('gallery-slider').scrollBy({left:320,behavior:'smooth'})"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-20">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-gallery-grid>
                @foreach($galleryImages as $img)
                <img src="{{ $img['src'] ? asset('storage/' . $img['src']) : 'https://placehold.co/600x400/e2e8f0/94a3b8?text=' . urlencode($img['alt'] ?? 'Gallery Image') }}"
                     alt="{{ $img['alt'] ?? 'Gallery image' }}"
                     data-gallery-item
                     class="w-full h-64 object-cover rounded-lg hover:shadow-lg transition-shadow"
                     onerror="this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=Gallery+Image'">
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ═══ COLOR ACCENT SECTION ═══ --}}
<section class="py-16">
    <div class="max-w-6xl mx-auto px-6">
        <div class="rounded-2xl overflow-hidden"
             data-field="accent_color" data-field-type="color" data-field-label="Accent Banner Color" data-field-group="Accent"
             data-color-value="{{ $fields['accent_color'] ?? '#2563eb' }}" data-color-target="background-color"
             style="background-color: {{ $fields['accent_color'] ?? '#2563eb' }};">
            <div class="px-12 py-16 text-center text-white">
                <h2 class="text-3xl font-bold mb-4"
                    data-field="accent_headline" data-field-type="text" data-field-label="Accent Headline" data-field-group="Accent">
                    {{ $fields['accent_headline'] ?? 'Customize Your Brand Colors' }}
                </h2>
                <p class="text-lg text-white/80 max-w-2xl mx-auto"
                   data-field="accent_description" data-field-type="textarea" data-field-label="Accent Description" data-field-group="Accent">
                    {{ $fields['accent_description'] ?? 'Click the color badge on this section to change the background color. This demonstrates the inline color picker field type.' }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CTA SECTION ═══ --}}
<section class="py-20" data-section-bg="cta_bg" data-field-label="CTA Background" data-bg-mode="cover"
         style="background-color: #2563eb;">
    <div class="max-w-4xl mx-auto px-6 text-center text-white">
        <h2 class="text-4xl font-bold mb-4"
            data-field="cta_headline" data-field-type="text" data-field-label="CTA Headline" data-field-group="Call to Action">
            {{ $fields['cta_headline'] ?? 'Ready to Get Started?' }}
        </h2>
        <p class="text-xl text-blue-100 mb-8"
           data-field="cta_description" data-field-type="textarea" data-field-label="CTA Description" data-field-group="Call to Action">
            {{ $fields['cta_description'] ?? "Let's discuss your project and see how we can help you achieve your goals." }}
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center"
             data-field="cta_buttons" data-field-type="button_group" data-field-label="CTA Buttons" data-field-group="Call to Action">
            @php
                $ctaButtons = $fields['cta_buttons'] ?? [
                    ['text' => 'Contact Us Today', 'link' => '/contact', 'style' => 'primary', 'visible' => true],
                ];
            @endphp
            @foreach($ctaButtons as $i => $btn)
                @if($btn['visible'] ?? true)
                <a href="{{ $btn['link'] ?? '#' }}"
                   data-button-index="{{ $i }}"
                   data-button-style="{{ $btn['style'] ?? 'primary' }}"
                   class="inline-block px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-blue-50 transition-colors text-lg">
                    {{ $btn['text'] ?? 'Button' }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
</section>

@endsection
