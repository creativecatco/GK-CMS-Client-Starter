# Design Library: Advanced Section Patterns

## 1. Philosophy: Go Beyond the Basics

This library contains production-quality, aesthetically pleasing section patterns that go beyond the simple layouts in `page-building.md`. When a user asks you to design a new page or make a page "look better," you should prioritize using these components.

Each component is designed to be responsive, accessible, and visually engaging. They combine Tailwind CSS for structure with CSS variables for branding, making them fully compatible with the CMS theme engine.

## 2. Hero Sections

### 2.1 Hero with Gradient Overlay & Angled Background

**Use Case:** A modern, visually dynamic hero section for homepages or key landing pages.

**Design Principles:**
- **Visual Hierarchy:** Large, bold heading captures attention first.
- **Depth:** The gradient overlay and angled background create a sense of depth and dimension.
- **Negative Space:** Ample padding around the content makes it easy to read.
- **Clear Call to Action:** Prominent, contrasting buttons guide the user.

**Blade Code:**

```blade
<div data-field="hero_bg" data-field-type="section_bg" class="relative overflow-hidden">
    @php
        $bgStyle = $page->sectionBgStyle("hero_bg", [
            'image' => 'media/ai-generated/default-hero.png',
            'color' => 'var(--color-secondary)',
            'overlay' => 'rgba(0,0,0,0.5)',
            'overlay_opacity' => '0.5'
        ]);
    @endphp
    <div class="absolute inset-0 bg-cover bg-center" style="{{ $bgStyle }}"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent opacity-75"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
        <div class="lg:w-1/2">
            <h1 data-field="hero_heading" data-field-type="text" class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight">{{ $fields["hero_heading"] ?? "Modern Solutions for a Digital World" }}</h1>
            <p data-field="hero_subheading" data-field-type="textarea" class="mt-6 text-xl text-gray-300">{{ $fields["hero_subheading"] ?? "We build beautiful, high-performance websites that drive results. Let us help you create a stunning online presence." }}</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <div data-field="hero_cta_primary" data-field-type="button">
                    {!! $page->renderButton("hero_cta_primary", ["text" => "Get Started", "link" => "/contact", "style" => "primary"]) !!}
                </div>
                <div data-field="hero_cta_secondary" data-field-type="button">
                    {!! $page->renderButton("hero_cta_secondary", ["text" => "Learn More", "link" => "/about", "style" => "secondary"]) !!}
                </div>
            </div>
        </div>
    </div>
</div>
```

## 3. Feature Grids

### 3.1 Three-Column Grid with Icons and Hover Effect

**Use Case:** Displaying key services, features, or benefits in a clean, organized way.

**Design Principles:**
- **Symmetry & Balance:** The three-column layout is visually stable and easy to scan.
- **Visual Cues:** Icons provide a quick, recognizable visual for each feature.
- **Interactivity:** The subtle hover effect (lift and shadow) provides feedback and makes the page feel more dynamic.

**Blade Code:**

```blade
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 data-field="features_heading" data-field-type="text" class="text-3xl font-extrabold text-gray-900">{{ $fields["features_heading"] ?? "Everything You Need, All in One Place" }}</h2>
            <p data-field="features_subheading" data-field-type="textarea" class="mt-4 text-lg text-gray-600">{{ $fields["features_subheading"] ?? "Our platform is designed to be powerful, flexible, and easy to use." }}</p>
        </div>
        <div class="mt-16">
            @php
                $features = $fields["feature_items"] ?? [
                    ["icon" => "monitor", "title" => "Beautiful Design", "description" => "Stunning, responsive templates that look great on any device."],
                    ["icon" => "zap", "title" => "Lightning Fast", "description" => "Optimized for performance to ensure a seamless user experience."],
                    ["icon" => "shield", "title" => "Rock-Solid Security", "description" => "Your data is safe with our enterprise-grade security features."]
                ];
                if (is_string($features)) $features = json_decode($features, true) ?? [];
            @endphp
            <div data-field="feature_items" data-field-type="repeater" class="grid grid-cols-1 md:grid-cols-3 gap-12">
                @foreach($features as $index => $feature)
                    <div data-repeater-item="{{ $index }}" class="bg-white p-8 rounded-xl shadow-lg transform hover:-translate-y-2 transition-transform duration-300">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full text-white" style="background-color: var(--color-primary);">
                            <span data-field="feature_items.{{$index}}.icon" data-field-type="icon" data-icon-name="{{ $feature["icon"] ?? "star" }}" class="w-6 h-6"></span>
                        </div>
                        <h3 data-field="feature_items.{{$index}}.title" data-field-type="text" class="mt-6 text-xl font-bold text-gray-900">{{ $feature["title"] ?? "Feature Title" }}</h3>
                        <p data-field="feature_items.{{$index}}.description" data-field-type="textarea" class="mt-2 text-base text-gray-600">{{ $feature["description"] ?? "Feature description goes here." }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
```

## 4. Testimonial Sections

### 4.1 Testimonial with Avatar and Company Logo

**Use Case:** Building social proof and trust by showcasing customer feedback.

**Design Principles:**
- **Human Connection:** The avatar creates a personal connection with the reader.
- **Credibility:** The company logo adds a layer of authenticity and authority.
- **Emphasis:** The large quote and bold name draw the eye to the most important information.

**Blade Code:**

```blade
<section class="py-20" style="background-color: var(--color-secondary);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div data-field="testimonial_avatar" data-field-type="image">
            <img class="mx-auto h-24 w-24 rounded-full object-cover" src="{{ asset("storage/" . ($fields["testimonial_avatar"] ?? "")) }}" alt="">
        </div>
        <blockquote class="mt-8">
            <p data-field="testimonial_quote" data-field-type="textarea" class="text-2xl font-medium text-white">{{ $fields["testimonial_quote"] ?? "This is the best service I have ever used. It has completely transformed my business and I couldn\'t be happier with the results!" }}</p>
        </blockquote>
        <footer class="mt-8">
            <div data-field="testimonial_author" data-field-type="text" class="text-lg font-semibold text-white">{{ $fields["testimonial_author"] ?? "Jane Doe" }}</div>
            <div data-field="testimonial_author_title" data-field-type="text" class="mt-1 text-base text-gray-300">{{ $fields["testimonial_author_title"] ?? "CEO, Example Inc." }}</div>
        </footer>
    </div>
</section>
```
