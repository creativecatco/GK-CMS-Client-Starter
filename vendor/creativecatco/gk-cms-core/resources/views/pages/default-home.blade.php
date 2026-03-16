{{--
    GKeys CMS - Default Home Page Template

    All sections use theme CSS variables for colors.
    All text/image/icon elements have data-field attributes for inline editing.
    Repeater sections use data-field-type="repeater" with data-repeater-fields.

    Fields:
    - hero_heading (text), hero_subheading (textarea), hero_cta (text), hero_cta_url (text), hero_image (image)
    - services_heading (text), services_subheading (textarea), services_items (repeater: title, desc, icon)
    - about_heading (text), about_text (textarea), about_image (image), about_cta (text), about_cta_url (text)
    - features_heading (text), features_subheading (textarea), features_items (repeater: title, desc, icon)
    - video_headline (text), video_description (textarea), video_embed (video)
    - stat1_number (text), stat1_label (text), stat2_number (text), stat2_label (text), stat3_number (text), stat3_label (text), stat4_number (text), stat4_label (text)
    - gallery_headline (text), gallery_description (textarea), gallery_images (gallery)
    - faq_headline (text), faq_description (textarea), faq_items (repeater: question, answer)
    - accent_color (color), accent_headline (text), accent_description (textarea)
    - testimonials_heading (text), testimonials_items (repeater: quote, name, role, image)
    - cta_heading (text), cta_text (textarea), cta_button (text), cta_button_url (text)
--}}
@extends('cms-core::layouts.app')

@section('content')

{{-- ===== HERO SECTION ===== --}}
<section class="relative py-24 md:py-36 overflow-hidden" style="background-color: var(--color-secondary)"
    data-section-bg="hero_bg" data-section-bg-type="gradient">
    {{-- Decorative background --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse at 30% 50%, var(--color-primary), transparent 60%), radial-gradient(ellipse at 70% 80%, var(--color-accent), transparent 50%)"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 leading-tight"
                    data-field="hero_heading" data-field-type="text">{{ $fields['hero_heading'] ?? 'Grow Your Business with Digital Solutions' }}</h1>
                <p class="text-lg md:text-xl text-gray-300 mb-8 leading-relaxed"
                    data-field="hero_subheading" data-field-type="textarea">{{ $fields['hero_subheading'] ?? 'We build beautiful, high-performance websites and digital strategies that convert visitors into loyal customers.' }}</p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ $fields['hero_cta_url'] ?? '/contact' }}"
                       class="inline-flex items-center px-8 py-4 rounded-lg text-lg font-bold transition-all hover:scale-105 hover:shadow-lg"
                       style="background-color: var(--color-primary); color: var(--color-secondary)"
                       data-field="hero_cta" data-field-type="text">{{ $fields['hero_cta'] ?? 'Get Started' }}</a>
                </div>
            </div>
            <div class="hidden lg:block">
                <img src="{{ $fields['hero_image'] ?? 'https://placehold.co/600x500/293726/d2eb00?text=Hero+Image' }}"
                     alt="Hero" class="rounded-2xl shadow-2xl w-full"
                     data-field="hero_image" data-field-type="image">
            </div>
        </div>
    </div>
</section>

{{-- ===== SERVICES SECTION ===== --}}
<section class="py-20" style="background-color: var(--color-bg)"
    data-section-bg="services_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4"
                data-field="services_heading" data-field-type="text">{{ $fields['services_heading'] ?? 'What We Do' }}</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto"
                data-field="services_subheading" data-field-type="textarea">{{ $fields['services_subheading'] ?? 'Our core services designed to help your business thrive in the digital landscape.' }}</p>
        </div>
        @php
            $servicesItems = $fields['services_items'] ?? [
                ['title' => 'Web Design & Development', 'desc' => 'Custom websites built for performance, accessibility, and conversions.', 'icon' => ''],
                ['title' => 'Digital Marketing', 'desc' => 'Data-driven marketing strategies including SEO, PPC, and social media.', 'icon' => ''],
                ['title' => 'Brand Strategy', 'desc' => 'Build a compelling brand identity that resonates with your target audience.', 'icon' => ''],
            ];
            // Merge flat icon keys (e.g. services_items.0.icon) into nested array
            foreach ($servicesItems as $i => &$sItem) {
                $flatKey = "services_items.{$i}.icon";
                if (empty($sItem['icon']) && !empty($fields[$flatKey])) {
                    $sItem['icon'] = $fields[$flatKey];
                }
            }
            unset($sItem);
            $defaultIcons = ['monitor', 'bar-chart', 'layers', 'star', 'zap', 'settings'];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
             data-field="services_items" data-field-type="repeater"
             data-repeater-fields='[{"key":"title","type":"text","label":"Title"},{"key":"desc","type":"textarea","label":"Description"},{"key":"icon","type":"icon","label":"Icon"}]'>
            @foreach($servicesItems as $index => $service)
                <div class="rounded-xl p-8 shadow-sm hover:shadow-md transition-all border border-gray-100 bg-white"
                     data-repeater-item="{{ $index }}">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5"
                         style="background-color: color-mix(in srgb, var(--color-primary) 15%, white)">
                        <span class="text-2xl" data-field="services_items.{{ $index }}.icon" data-field-type="icon" data-icon-name="{{ $service['icon'] ?? ($defaultIcons[$index] ?? 'star') }}"><svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg></span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3"
                        data-field="services_items.{{ $index }}.title" data-field-type="text">{{ $service['title'] ?? 'Service' }}</h3>
                    <p class="text-gray-600 leading-relaxed"
                        data-field="services_items.{{ $index }}.desc" data-field-type="textarea">{{ $service['desc'] ?? 'Service description goes here.' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== ABOUT / WHY CHOOSE US SECTION ===== --}}
<section class="py-20" style="background-color: #f6f1e6"
    data-section-bg="about_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <img src="{{ $fields['about_image'] ?? 'https://placehold.co/600x450/293726/d2eb00?text=About+Us' }}"
                     alt="About us" class="rounded-2xl shadow-lg w-full"
                     data-field="about_image" data-field-type="image">
            </div>
            <div>
                <h2 class="text-3xl md:text-4xl font-bold mb-6"
                    data-field="about_heading" data-field-type="text">{{ $fields['about_heading'] ?? 'Why Choose Us' }}</h2>
                <p class="text-gray-600 text-lg leading-relaxed mb-8"
                    data-field="about_text" data-field-type="textarea">{{ $fields['about_text'] ?? 'We combine creativity with technology to deliver digital experiences that drive real business growth. Our team of experts works closely with you to understand your goals and create solutions that exceed expectations. With years of experience and a passion for innovation, we are your trusted partner in digital transformation.' }}</p>
                <a href="{{ $fields['about_cta_url'] ?? '/about' }}"
                   class="inline-flex items-center px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105"
                   style="background-color: var(--color-secondary); color: white"
                   data-field="about_cta" data-field-type="text">{{ $fields['about_cta'] ?? 'Learn More About Us' }}</a>
            </div>
        </div>
    </div>
</section>

{{-- ===== FEATURES / HIGHLIGHTS SECTION ===== --}}
<section class="py-20" style="background-color: var(--color-bg)"
    data-section-bg="features_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4"
                data-field="features_heading" data-field-type="text">{{ $fields['features_heading'] ?? 'Built for Results' }}</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto"
                data-field="features_subheading" data-field-type="textarea">{{ $fields['features_subheading'] ?? 'Every project we deliver is designed with measurable outcomes in mind.' }}</p>
        </div>
        @php
            $featuresItems = $fields['features_items'] ?? [
                ['title' => 'Mobile-First Design', 'desc' => 'Every site is built responsive from the ground up, ensuring a perfect experience on any device.', 'icon' => ''],
                ['title' => 'SEO Optimized', 'desc' => 'Built-in best practices for search engine visibility, structured data, and fast load times.', 'icon' => ''],
                ['title' => 'Conversion Focused', 'desc' => 'Strategic layouts and calls-to-action designed to turn visitors into customers.', 'icon' => ''],
                ['title' => 'Ongoing Support', 'desc' => 'We provide continued support and maintenance to keep your digital presence running smoothly.', 'icon' => ''],
            ];
            // Merge flat icon keys (e.g. features_items.0.icon) into nested array
            foreach ($featuresItems as $i => &$fItem) {
                $flatKey = "features_items.{$i}.icon";
                if (empty($fItem['icon']) && !empty($fields[$flatKey])) {
                    $fItem['icon'] = $fields[$flatKey];
                }
            }
            unset($fItem);
            $featureIcons = ['smartphone', 'search', 'dollar-sign', 'headphones', 'zap', 'lock'];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8"
             data-field="features_items" data-field-type="repeater"
             data-repeater-fields='[{"key":"title","type":"text","label":"Title"},{"key":"desc","type":"textarea","label":"Description"},{"key":"icon","type":"icon","label":"Icon"}]'>
            @foreach($featuresItems as $index => $feature)
                <div class="flex gap-5 p-6 rounded-xl hover:bg-gray-50 transition-colors" data-repeater-item="{{ $index }}">
                    <div class="w-12 h-12 rounded-lg flex-shrink-0 flex items-center justify-center"
                         style="background-color: var(--color-secondary)">
                        <span class="text-xl" style="color: var(--color-primary)" data-field="features_items.{{ $index }}.icon" data-field-type="icon" data-icon-name="{{ $feature['icon'] ?? ($featureIcons[$index] ?? 'star') }}"><svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2"
                            data-field="features_items.{{ $index }}.title" data-field-type="text">{{ $feature['title'] ?? 'Feature' }}</h3>
                        <p class="text-gray-600"
                            data-field="features_items.{{ $index }}.desc" data-field-type="textarea">{{ $feature['desc'] ?? 'Feature description goes here.' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== VIDEO SECTION ===== --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="video_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4"
                data-field="video_headline" data-field-type="text" data-field-label="Video Headline" data-field-group="Video">
                {{ $fields['video_headline'] ?? 'See Our Work in Action' }}
            </h2>
            <p class="text-lg text-gray-300 max-w-2xl mx-auto"
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
                <div class="flex items-center justify-center" style="aspect-ratio:16/9; background-color: rgba(0,0,0,0.3);">
                    <p class="text-gray-300 text-lg">Click "Edit Video" to add a video</p>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ===== STATS SECTION ===== --}}
<section class="py-16" style="background-color: #f6f1e6"
    data-section-bg="stats_bg" data-section-bg-type="color">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-extrabold mb-2" style="color: var(--color-secondary)"
                     data-field="stat1_number" data-field-type="text" data-field-label="Stat 1 Number" data-field-group="Stats">
                    {{ $fields['stat1_number'] ?? '150+' }}
                </div>
                <div class="text-gray-600"
                     data-field="stat1_label" data-field-type="text" data-field-label="Stat 1 Label" data-field-group="Stats">
                    {{ $fields['stat1_label'] ?? 'Projects Completed' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2" style="color: var(--color-secondary)"
                     data-field="stat2_number" data-field-type="text" data-field-label="Stat 2 Number" data-field-group="Stats">
                    {{ $fields['stat2_number'] ?? '50+' }}
                </div>
                <div class="text-gray-600"
                     data-field="stat2_label" data-field-type="text" data-field-label="Stat 2 Label" data-field-group="Stats">
                    {{ $fields['stat2_label'] ?? 'Happy Clients' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2" style="color: var(--color-secondary)"
                     data-field="stat3_number" data-field-type="text" data-field-label="Stat 3 Number" data-field-group="Stats">
                    {{ $fields['stat3_number'] ?? '10+' }}
                </div>
                <div class="text-gray-600"
                     data-field="stat3_label" data-field-type="text" data-field-label="Stat 3 Label" data-field-group="Stats">
                    {{ $fields['stat3_label'] ?? 'Years Experience' }}
                </div>
            </div>
            <div>
                <div class="text-4xl font-extrabold mb-2" style="color: var(--color-secondary)"
                     data-field="stat4_number" data-field-type="text" data-field-label="Stat 4 Number" data-field-group="Stats">
                    {{ $fields['stat4_number'] ?? '99%' }}
                </div>
                <div class="text-gray-600"
                     data-field="stat4_label" data-field-type="text" data-field-label="Stat 4 Label" data-field-group="Stats">
                    {{ $fields['stat4_label'] ?? 'Client Satisfaction' }}
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== GALLERY SECTION ===== --}}
<section class="py-20" style="background-color: var(--color-bg)"
    data-section-bg="gallery_bg" data-section-bg-type="color">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4"
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
                    <img src="{{ $img['src'] ? asset('storage/' . $img['src']) : 'https://placehold.co/600x400/293726/d2eb00?text=' . urlencode($img['alt'] ?? 'Gallery Image') }}"
                         alt="{{ $img['alt'] ?? 'Gallery image' }}"
                         data-gallery-item
                         class="w-80 h-64 object-cover rounded-lg flex-shrink-0 snap-center"
                         onerror="this.src='https://placehold.co/600x400/293726/d2eb00?text=Gallery+Image'">
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
                <img src="{{ $img['src'] ? asset('storage/' . $img['src']) : 'https://placehold.co/600x400/293726/d2eb00?text=' . urlencode($img['alt'] ?? 'Gallery Image') }}"
                     alt="{{ $img['alt'] ?? 'Gallery image' }}"
                     data-gallery-item
                     class="w-full h-64 object-cover rounded-lg hover:shadow-lg transition-shadow"
                     onerror="this.src='https://placehold.co/600x400/293726/d2eb00?text=Gallery+Image'">
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ===== FAQ SECTION ===== --}}
<section class="py-20" style="background-color: #f6f1e6"
    data-section-bg="faq_bg" data-section-bg-type="color">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4"
                data-field="faq_headline" data-field-type="text" data-field-label="FAQ Headline" data-field-group="FAQ">
                {{ $fields['faq_headline'] ?? 'Frequently Asked Questions' }}
            </h2>
            <p class="text-lg text-gray-600"
               data-field="faq_description" data-field-type="textarea" data-field-label="FAQ Description" data-field-group="FAQ">
                {{ $fields['faq_description'] ?? 'Find answers to common questions about our services.' }}
            </p>
        </div>

        @php
            $faqItems = $fields['faq_items'] ?? [
                ['question' => 'What services do you offer?', 'answer' => 'We offer web design, SEO, app development, and digital marketing services tailored to your business needs.'],
                ['question' => 'How long does a typical project take?', 'answer' => 'Most projects are completed within 4-8 weeks, depending on scope and complexity. We provide detailed timelines during our initial consultation.'],
                ['question' => 'Do you offer ongoing support?', 'answer' => 'Yes! We offer maintenance packages that include updates, security monitoring, and priority support to keep your site running smoothly.'],
                ['question' => 'What is your pricing structure?', 'answer' => 'We offer flexible pricing based on project scope. Contact us for a free quote tailored to your specific requirements.'],
            ];
        @endphp

        <div class="space-y-4"
             data-field="faq_items" data-field-type="repeater" data-field-label="FAQ Items" data-field-group="FAQ"
             data-repeater-fields='[{"key":"question","type":"text","label":"Question"},{"key":"answer","type":"textarea","label":"Answer"}]'>
            @foreach($faqItems as $index => $faq)
            <div data-repeater-item="{{ $index }}" class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                <button class="w-full text-left px-6 py-4 hover:bg-gray-50 transition-colors flex items-center justify-between"
                        onclick="this.parentElement.classList.toggle('faq-open'); this.querySelector('svg').classList.toggle('rotate-180');">
                    <span class="text-lg font-semibold text-gray-900"
                          data-field="faq_items.{{ $index }}.question" data-field-type="text">{{ $faq['question'] ?? 'Question' }}</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform flex-shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="px-6 py-4 text-gray-600 hidden faq-answer border-t border-gray-100">
                    <p data-field="faq_items.{{ $index }}.answer" data-field-type="textarea">{{ $faq['answer'] ?? 'Answer' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .faq-open .faq-answer { display: block !important; }
</style>

{{-- ===== COLOR ACCENT SECTION ===== --}}
<section class="py-16" style="background-color: var(--color-bg)"
    data-section-bg="accent_section_bg" data-section-bg-type="color">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl overflow-hidden"
             data-field="accent_color" data-field-type="color" data-field-label="Accent Banner Color" data-field-group="Accent"
             data-color-value="{{ $fields['accent_color'] ?? '#2563eb' }}" data-color-target="background-color"
             style="background-color: {{ $fields['accent_color'] ?? 'var(--color-primary)' }};">
            <div class="px-12 py-16 text-center text-white">
                <h2 class="text-3xl font-bold mb-4"
                    data-field="accent_headline" data-field-type="text" data-field-label="Accent Headline" data-field-group="Accent">
                    {{ $fields['accent_headline'] ?? 'Ready to Transform Your Brand?' }}
                </h2>
                <p class="text-lg text-white/80 max-w-2xl mx-auto"
                   data-field="accent_description" data-field-type="textarea" data-field-label="Accent Description" data-field-group="Accent">
                    {{ $fields['accent_description'] ?? 'This color banner is fully customizable. Click the color badge to change the background color using the inline color picker.' }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ===== TESTIMONIALS SECTION ===== --}}
<section class="py-20" style="background-color: var(--color-secondary)"
    data-section-bg="testimonials_bg" data-section-bg-type="color">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4"
                data-field="testimonials_heading" data-field-type="text">{{ $fields['testimonials_heading'] ?? 'What Our Clients Say' }}</h2>
        </div>
        @php
            $testimonials = $fields['testimonials_items'] ?? [
                ['quote' => 'They transformed our online presence completely. Our leads have increased by 300% since launching the new site.', 'name' => 'Sarah Johnson', 'role' => 'CEO, TechStart Inc.', 'image' => ''],
                ['quote' => 'Professional, creative, and results-driven. The best investment we have made for our business this year.', 'name' => 'Michael Chen', 'role' => 'Founder, GreenLeaf Co.', 'image' => ''],
                ['quote' => 'Their attention to detail and commitment to quality is unmatched. Highly recommend their services.', 'name' => 'Emily Davis', 'role' => 'Marketing Director, Apex Group', 'image' => ''],
            ];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8"
             data-field="testimonials_items" data-field-type="repeater"
             data-repeater-fields='[{"key":"quote","type":"textarea","label":"Quote"},{"key":"name","type":"text","label":"Name"},{"key":"role","type":"text","label":"Role"},{"key":"image","type":"image","label":"Photo"}]'>
            @foreach($testimonials as $index => $testimonial)
                <div class="bg-white/10 backdrop-blur rounded-xl p-8" data-repeater-item="{{ $index }}">
                    <div class="mb-4" style="color: var(--color-primary)">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    </div>
                    <p class="text-white/90 mb-6 leading-relaxed"
                        data-field="testimonials_items.{{ $index }}.quote" data-field-type="textarea">{{ $testimonial['quote'] ?? 'Client testimonial goes here.' }}</p>
                    <div class="flex items-center gap-3">
                        @if(!empty($testimonial['image']))
                            <img src="{{ str_starts_with($testimonial['image'], 'http') ? $testimonial['image'] : asset('storage/' . $testimonial['image']) }}"
                                 alt="{{ $testimonial['name'] ?? '' }}" class="w-10 h-10 rounded-full object-cover"
                                 data-field="testimonials_items.{{ $index }}.image" data-field-type="image">
                        @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                 style="background-color: var(--color-primary); color: var(--color-secondary)"
                                 data-field="testimonials_items.{{ $index }}.image" data-field-type="image">
                                {{ strtoupper(substr($testimonial['name'] ?? 'C', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-white font-semibold text-sm"
                                data-field="testimonials_items.{{ $index }}.name" data-field-type="text">{{ $testimonial['name'] ?? 'Client Name' }}</p>
                            <p class="text-white/60 text-xs"
                                data-field="testimonials_items.{{ $index }}.role" data-field-type="text">{{ $testimonial['role'] ?? 'Position, Company' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== BOTTOM CTA SECTION ===== --}}
<section class="py-20" style="background-color: #f6f1e6"
    data-section-bg="cta_bg" data-section-bg-type="color">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4"
            data-field="cta_heading" data-field-type="text">{{ $fields['cta_heading'] ?? 'Ready to Get Started?' }}</h2>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto"
            data-field="cta_text" data-field-type="textarea">{{ $fields['cta_text'] ?? 'Contact us today for a free consultation and discover how we can help grow your business online.' }}</p>
        <a href="{{ $fields['cta_button_url'] ?? '/contact' }}"
           class="inline-flex items-center px-8 py-4 rounded-lg text-lg font-bold transition-all hover:scale-105 hover:shadow-lg"
           style="background-color: var(--color-secondary); color: white"
           data-field="cta_button" data-field-type="text">{{ $fields['cta_button'] ?? 'Contact Us Today' }}</a>
    </div>
</section>

@push('scripts')
<script>
(function() {
    // Icon library matching the inline editor
    const icons = {
        'phone': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'mail': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'map-pin': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'globe': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'star': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'heart': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'check-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'shield': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'zap': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'clock': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'users': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'settings': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'home': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'code': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        'monitor': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'smartphone': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
        'trending-up': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'dollar-sign': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'award': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
        'target': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'briefcase': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'camera': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
        'edit': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'search': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'lock': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'truck': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'message-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        'bar-chart': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
        'layers': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'play-circle': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
        'headphones': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
    };
    // Render icons from data-icon-name attributes
    document.querySelectorAll('[data-icon-name]').forEach(function(el) {
        const name = el.dataset.iconName;
        if (name && icons[name]) {
            const existingSvg = el.querySelector('svg');
            const temp = document.createElement('div');
            temp.innerHTML = icons[name];
            const newSvg = temp.querySelector('svg');
            if (existingSvg) {
                // Preserve size classes from original
                const cls = existingSvg.getAttribute('class');
                if (cls) newSvg.setAttribute('class', cls);
            } else {
                // Default size
                newSvg.setAttribute('class', 'w-6 h-6');
            }
            el.innerHTML = '';
            el.appendChild(newSvg);
        }
    });
})();
</script>
@endpush

@endsection
