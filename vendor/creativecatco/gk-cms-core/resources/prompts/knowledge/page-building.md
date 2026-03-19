# Page Building

## Full Website Build Workflow

When building a complete website from scratch, follow this order:

1. **Gather information** — Business name, services, target audience, style preferences, reference URLs
2. **Propose a site plan** — List the pages you'll create, wait for user confirmation
3. **Execute in order:**
   - `update_settings` — Site name, tagline, company info, contact details
   - `update_theme` — Colors, fonts (use `css-variables` module for reference)
   - Generate images — Hero banners, backgrounds for each page
   - Create homepage — `create_page` with full template, fields, images
   - `update_settings` with `home_page_id` — Set the homepage
   - Create other pages — About, Services, Contact, etc.
   - `update_menu` — Set up navigation with links to all pages
   - `update_css` — Any global custom CSS needed
4. **Narrate each step** briefly to the user

## Common Section Patterns

### Hero Section

```blade
<section data-field="hero_bg" data-field-type="section_bg" class="relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32">
        <div class="max-w-3xl">
            <h1 data-field="hero_heading" data-field-type="text" class="text-4xl md:text-5xl lg:text-6xl font-bold text-white" style="font-family: var(--font-heading);">
                {{ $fields['hero_heading'] ?? 'Welcome to Our Site' }}
            </h1>
            <p data-field="hero_subheading" data-field-type="textarea" class="mt-6 text-xl text-white/90">
                {{ $fields['hero_subheading'] ?? 'We help businesses grow with innovative solutions.' }}
            </p>
            <div class="mt-8" data-field="hero_cta" data-field-type="button">
                {!! $page->renderButton('hero_cta', ['text' => 'Get Started', 'link' => '/contact', 'style' => 'primary']) !!}
            </div>
        </div>
    </div>
</section>
```

### Features/Services Grid

```blade
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 data-field="features_heading" data-field-type="text" class="text-3xl md:text-4xl font-bold" style="font-family: var(--font-heading); color: var(--color-text);">
                {{ $fields['features_heading'] ?? 'Our Services' }}
            </h2>
            <p data-field="features_subheading" data-field-type="textarea" class="mt-4 text-lg" style="color: var(--color-text); opacity: 0.7;">
                {{ $fields['features_subheading'] ?? 'What we offer' }}
            </p>
        </div>
        @php
            $items = $fields['features_items'] ?? [
                ['icon' => 'shield', 'title' => 'Feature 1', 'desc' => 'Description'],
                ['icon' => 'zap', 'title' => 'Feature 2', 'desc' => 'Description'],
                ['icon' => 'heart', 'title' => 'Feature 3', 'desc' => 'Description'],
            ];
            if (is_string($items)) $items = json_decode($items, true) ?? [];
        @endphp
        <div data-field="features_items" data-field-type="repeater" class="grid md:grid-cols-3 gap-8">
            @foreach($items as $index => $item)
                <div data-repeater-item="{{ $index }}" class="text-center p-6 rounded-lg shadow-sm border">
                    <span data-field="features_items.{{ $index }}.icon" data-field-type="icon" data-icon-name="{{ $item['icon'] ?? 'star' }}" class="w-12 h-12 mx-auto" style="color: var(--color-primary);"></span>
                    <h3 data-field="features_items.{{ $index }}.title" data-field-type="text" class="mt-4 text-xl font-semibold" style="color: var(--color-text);">{{ $item['title'] ?? 'Feature' }}</h3>
                    <p data-field="features_items.{{ $index }}.desc" data-field-type="textarea" class="mt-2" style="color: var(--color-text); opacity: 0.7;">{{ $item['desc'] ?? 'Description' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

### Call-to-Action (CTA) Section

```blade
<section class="py-16 md:py-20" style="background-color: var(--color-primary);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 data-field="cta_heading" data-field-type="text" class="text-3xl md:text-4xl font-bold text-white" style="font-family: var(--font-heading);">
            {{ $fields['cta_heading'] ?? 'Ready to Get Started?' }}
        </h2>
        <p data-field="cta_text" data-field-type="textarea" class="mt-4 text-xl text-white/90">
            {{ $fields['cta_text'] ?? 'Contact us today for a free consultation.' }}
        </p>
        <div class="mt-8" data-field="cta_button" data-field-type="button">
            {!! $page->renderButton('cta_button', ['text' => 'Contact Us', 'link' => '/contact', 'style' => 'secondary']) !!}
        </div>
    </div>
</section>
```

### Contact Section

```blade
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12">
            <div>
                <h2 data-field="contact_heading" data-field-type="text" class="text-3xl font-bold" style="font-family: var(--font-heading); color: var(--color-text);">
                    {{ $fields['contact_heading'] ?? 'Get In Touch' }}
                </h2>
                <p data-field="contact_text" data-field-type="textarea" class="mt-4" style="color: var(--color-text); opacity: 0.7;">
                    {{ $fields['contact_text'] ?? 'We would love to hear from you.' }}
                </p>
                <div class="mt-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <span data-icon-name="phone" class="w-5 h-5" style="color: var(--color-primary);"></span>
                        <span data-field="contact_phone" data-field-type="text">{{ $fields['contact_phone'] ?? '(555) 123-4567' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span data-icon-name="mail" class="w-5 h-5" style="color: var(--color-primary);"></span>
                        <span data-field="contact_email" data-field-type="text">{{ $fields['contact_email'] ?? 'info@example.com' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span data-icon-name="map-pin" class="w-5 h-5" style="color: var(--color-primary);"></span>
                        <span data-field="contact_address" data-field-type="text">{{ $fields['contact_address'] ?? '123 Main St, City, State' }}</span>
                    </div>
                </div>
            </div>
            <div data-field="contact_form" data-field-type="richtext">
                {!! $fields['contact_form'] ?? '<p>Contact form placeholder — add a form embed or plugin here.</p>' !!}
            </div>
        </div>
    </div>
</section>
```

### Testimonials Section

```blade
<section class="py-16 md:py-20" style="background-color: var(--color-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 data-field="testimonials_heading" data-field-type="text" class="text-3xl font-bold text-center mb-12" style="font-family: var(--font-heading); color: var(--color-text);">
            {{ $fields['testimonials_heading'] ?? 'What Our Clients Say' }}
        </h2>
        @php
            $testimonials = $fields['testimonials_items'] ?? [
                ['quote' => 'Great service!', 'name' => 'John Doe', 'role' => 'CEO'],
            ];
            if (is_string($testimonials)) $testimonials = json_decode($testimonials, true) ?? [];
        @endphp
        <div data-field="testimonials_items" data-field-type="repeater" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($testimonials as $index => $item)
                <div data-repeater-item="{{ $index }}" class="p-6 rounded-lg shadow-sm border">
                    <p data-field="testimonials_items.{{ $index }}.quote" data-field-type="textarea" class="italic" style="color: var(--color-text);">
                        "{{ $item['quote'] ?? 'Great experience!' }}"
                    </p>
                    <div class="mt-4">
                        <p data-field="testimonials_items.{{ $index }}.name" data-field-type="text" class="font-semibold" style="color: var(--color-text);">{{ $item['name'] ?? 'Client Name' }}</p>
                        <p data-field="testimonials_items.{{ $index }}.role" data-field-type="text" class="text-sm" style="color: var(--color-text); opacity: 0.6;">{{ $item['role'] ?? 'Position' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

## Page Checklist

Before finishing any page, verify:

- [ ] Every editable element has `data-field` and `data-field-type`
- [ ] All colors use CSS variables (no hardcoded hex)
- [ ] All field values have defaults with `??`
- [ ] Real images are placed (no placeholders)
- [ ] Responsive design works (check with `render_page`)
- [ ] One `<h1>` per page, proper heading hierarchy
- [ ] SEO title and description are set
- [ ] Icon rendering script is included if icons are used
- [ ] Buttons use `renderButton()` not `button()`
