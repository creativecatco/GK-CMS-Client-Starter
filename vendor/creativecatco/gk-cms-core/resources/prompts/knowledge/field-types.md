# Field Types Reference

Every editable element in a template MUST have `data-field="key"` and `data-field-type="type"` attributes. This is how the inline editor and `update_page_fields` tool know what to edit.

## Quick Reference

| Type | Value Format | Template Syntax |
|------|-------------|-----------------|
| `text` | Plain string | `{{ $fields['key'] ?? 'Default' }}` |
| `textarea` | Plain string (multi-line) | `{{ $fields['key'] ?? 'Default' }}` |
| `richtext` | HTML string | `{!! $fields['key'] ?? '<p>Default</p>' !!}` |
| `image` | Storage-relative path string | `asset('storage/' . ($fields['key'] ?? ''))` |
| `button` | JSON object `{text, link, style}` | `$page->renderButton('key', defaults)` |
| `button_group` | JSON array of button objects | See `button-fields` module |
| `icon` | Icon name string | `data-icon-name="{{ $fields['key'] ?? 'star' }}"` |
| `color` | CSS color string | `style="background-color: {{ $fields['key'] ?? 'var(--color-primary)' }}"` |
| `video` | URL string | Iframe embed (handle YouTube URL conversion) |
| `gallery` | JSON array of image paths | Parse JSON if string, loop images |
| `section_bg` | **JSON object** (NOT a string) | `data-field-type="section_bg"` on `<section>` — layout auto-applies styles |
| `repeater` | JSON array of objects | `@foreach` with dot notation sub-fields |

## text

Single-line editable text. The most common field type.

```blade
<h2 data-field="heading" data-field-type="text">{{ $fields['heading'] ?? 'Default Heading' }}</h2>
<span data-field="label" data-field-type="text">{{ $fields['label'] ?? 'Label' }}</span>
```

**Update format:** `{"heading": "New Heading Text"}`

## textarea

Multi-line plain text. No HTML allowed.

```blade
<p data-field="description" data-field-type="textarea">{{ $fields['description'] ?? 'Default description text.' }}</p>
```

**Update format:** `{"description": "New multi-line\ntext content"}`

## richtext

HTML content with formatting. Use `{!! !!}` (unescaped) instead of `{{ }}`.

```blade
<div data-field="content" data-field-type="richtext">{!! $fields['content'] ?? '<p>Default content</p>' !!}</div>
```

**Update format:** `{"content": "<p>Formatted <strong>HTML</strong> content</p>"}`

## image

Image field. Value is a **storage-relative path** (e.g., `"media/ai-generated/hero.png"` or `"uploads/photo.jpg"`).

```blade
<img src="{{ asset('storage/' . ($fields['hero_image'] ?? '')) }}"
     data-field="hero_image" data-field-type="image"
     alt="{{ $fields['hero_image_alt'] ?? 'Hero image' }}">
```

**Update format:** `{"hero_image": "media/ai-generated/hero.png"}`

Do NOT include `/storage/` prefix or full URLs — the template adds `asset('storage/...')` automatically.

For detailed image workflow, load the `image-workflow` module.

## section_bg

Section background with color, image, and overlay support. This is a **JSON object**, NOT a simple string.

For complete documentation, load the `section-bg` module.

**Quick reference — update format:**
```json
{"hero_bg": {"image": "media/ai-generated/hero.png", "mode": "cover", "color": "#1a1a2e", "colorType": "solid", "gradient": null, "overlay": {"type": "none"}}}
```

## icon

Renders an SVG icon from a predefined set. Requires the icon rendering script (load `icon-library` module).

```blade
<span data-field="feature_icon" data-field-type="icon" data-icon-name="{{ $fields['feature_icon'] ?? 'star' }}" class="w-12 h-12"></span>
```

**Update format:** `{"feature_icon": "shield"}`

Common icons: monitor, smartphone, search, dollar-sign, shield, zap, heart, star, users, globe, mail, phone, map-pin, clock, code, check-circle, arrow-right, home, briefcase, target

## color

CSS color value for dynamic styling.

```blade
<section data-field="bg_color" data-field-type="color" style="background-color: {{ $fields['bg_color'] ?? 'var(--color-primary)' }}">
```

**Update format:** `{"bg_color": "#2563eb"}`

## video

Video embed URL (YouTube, Vimeo, etc.).

```blade
<div data-field="intro_video" data-field-type="video">
    @php $videoUrl = $fields['intro_video'] ?? ''; @endphp
    @if($videoUrl)
        <iframe src="{{ $videoUrl }}" class="w-full aspect-video rounded-lg" allowfullscreen></iframe>
    @endif
</div>
```

**Update format:** `{"intro_video": "https://www.youtube.com/embed/VIDEO_ID"}`

## button & button_group

For complete documentation, load the `button-fields` module.

## repeater

For complete documentation, load the `repeater-fields` module.

## gallery

JSON array of image paths.

```blade
@php
    $images = $fields['gallery_images'] ?? [];
    if (is_string($images)) $images = json_decode($images, true) ?? [];
@endphp
<div data-field="gallery_images" data-field-type="gallery" class="grid grid-cols-2 md:grid-cols-3 gap-4">
    @foreach($images as $img)
        <img src="{{ asset('storage/' . $img) }}" class="rounded-lg" alt="">
    @endforeach
</div>
```

**Update format:** `{"gallery_images": ["uploads/img1.jpg", "uploads/img2.jpg", "uploads/img3.jpg"]}`
