# Template Rules

## Template Structure

Every page template follows this structure:

```blade
@extends('cms-core::layouts.app')
@section('content')
{{-- Page sections go here --}}
@endsection
@push('scripts')
{{-- Page-specific JavaScript (e.g., icon rendering) --}}
@endpush
```

If `@extends` is omitted, the system auto-wraps the template in the default layout. You can omit it for simplicity — the CMS handles it.

## What the Layout Already Provides (DO NOT Include)

The layout automatically includes:
- **Tailwind CSS** (CDN + JIT compiler) — use Tailwind classes freely
- **Alpine.js** — for interactive components
- **Google Fonts** — loaded from theme settings
- **CSS variables** — from theme configuration (load `css-variables` module for reference)
- **SEO meta tags** — from page seo_title and seo_description
- **Header and footer** — rendered from their own page templates
- **Analytics/tracking** — from site settings
- **Inline editor** — for frontend editing

Do NOT add `<html>`, `<head>`, `<body>`, Tailwind CDN links, or font imports — they're already there.

## Inline Editing (CRITICAL)

Every editable element MUST have these attributes:

| Attribute | Required | Purpose |
|-----------|----------|---------|
| `data-field="field_key"` | Yes | Unique identifier for the field |
| `data-field-type="type"` | Yes | Tells the editor what UI to show |
| `data-field-label="Label"` | No | Human-readable label in the editor |
| `data-field-group="Group"` | No | Groups related fields in the editor |

Without these attributes, the content cannot be edited from the frontend.

## Accessing Field Values

Two equivalent methods:

```blade
{{-- Method 1: Direct array access with default --}}
{{ $fields['hero_heading'] ?? 'Default Heading' }}

{{-- Method 2: Helper method --}}
{{ $page->field('hero_heading', 'Default Heading') }}

{{-- For HTML content (richtext fields only): --}}
{!! $fields['content'] ?? '<p>Default content</p>' !!}
```

**Always provide defaults with `??`.** Never assume a field has a value — it may be empty on first load.

## Section Patterns

Standard section spacing and container:

```blade
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section content --}}
    </div>
</section>
```

Common section layouts:

| Pattern | Classes |
|---------|---------|
| Centered heading | `text-center max-w-3xl mx-auto` |
| Two columns | `grid md:grid-cols-2 gap-8 lg:gap-12 items-center` |
| Three columns | `grid md:grid-cols-3 gap-6 lg:gap-8` |
| Four columns | `grid sm:grid-cols-2 lg:grid-cols-4 gap-6` |

## Responsive Design

Use mobile-first approach with Tailwind breakpoints:
- Default = mobile
- `sm:` = 640px+
- `md:` = 768px+
- `lg:` = 1024px+
- `xl:` = 1280px+

Example: `class="text-2xl md:text-3xl lg:text-4xl"`

## Heading Hierarchy

- One `<h1>` per page (usually the hero heading)
- `<h2>` for section headings
- `<h3>` for sub-section headings or card titles
- Never skip levels (e.g., h1 → h3)

## Template Validation

The CMS validates templates before saving. Common rejection reasons:
- Template too short (< 50 chars)
- Unmatched Blade directives (@if without @endif, @foreach without @endforeach)
- No `data-field` attributes found
- Dropping more than 50% of existing fields (likely a mistake)

## When to Use update_page_template vs update_page_fields

| Change | Correct Tool |
|--------|-------------|
| Change text, images, content | `update_page_fields` |
| Add a new section | `update_page_template` |
| Rearrange sections | `update_page_template` |
| Change layout structure | `update_page_template` |
| Fix broken template syntax | `update_page_template` |

**Always use `get_page_info` before modifying a template** to see the current code and preserve existing field keys.
