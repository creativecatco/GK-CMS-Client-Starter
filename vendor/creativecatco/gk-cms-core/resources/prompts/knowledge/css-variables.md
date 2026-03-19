# CSS Variables

## Core Rule

**Never hardcode colors or fonts.** Always use CSS variables so the site's theme can be changed from the admin panel without editing templates.

## Variable Reference

| Variable | Usage | Example |
|----------|-------|---------|
| `var(--color-primary)` | Buttons, accents, highlights, CTAs | `background-color: var(--color-primary)` |
| `var(--color-secondary)` | Dark backgrounds, contrast areas | `background-color: var(--color-secondary)` |
| `var(--color-accent)` | Links, hover states, subtle highlights | `color: var(--color-accent)` |
| `var(--color-text)` | Body text, paragraphs | `color: var(--color-text)` |
| `var(--color-bg)` | Page background | `background-color: var(--color-bg)` |
| `var(--header-bg)` | Header background | Used by header template |
| `var(--footer-bg)` | Footer background | Used by footer template |
| `var(--font-heading)` | Heading font family | `font-family: var(--font-heading)` |
| `var(--font-body)` | Body text font family | `font-family: var(--font-body)` |

## Usage in Templates

```blade
{{-- Buttons --}}
<a href="/contact" class="inline-block px-6 py-3 rounded-lg text-white"
   style="background-color: var(--color-primary);">
    Get Started
</a>

{{-- Section with themed background --}}
<section style="background-color: var(--color-secondary); color: white;">
    <div class="max-w-7xl mx-auto px-4 py-16">
        <h2 style="font-family: var(--font-heading);">Section Title</h2>
        <p style="font-family: var(--font-body);">Content text</p>
    </div>
</section>

{{-- Accent-colored elements --}}
<a href="/about" style="color: var(--color-accent);">Learn More</a>
```

## How Theme Variables Are Set

Theme variables come from site settings, managed via the `update_theme` tool:

| Setting Key | CSS Variable |
|-------------|-------------|
| `theme_primary_color` | `--color-primary` |
| `theme_secondary_color` | `--color-secondary` |
| `theme_accent_color` | `--color-accent` |
| `theme_text_color` | `--color-text` |
| `theme_bg_color` | `--color-bg` |
| `theme_header_bg` | `--header-bg` |
| `theme_footer_bg` | `--footer-bg` |
| `theme_font_heading` | `--font-heading` |
| `theme_font_body` | `--font-body` |

To change the site's color scheme, use `update_theme` — not template edits.

## Combining with Tailwind

You can combine CSS variables with Tailwind utility classes:

```blade
{{-- Tailwind for layout, CSS variables for colors --}}
<div class="rounded-lg shadow-lg p-8" style="background-color: var(--color-primary);">
    <h3 class="text-2xl font-bold text-white" style="font-family: var(--font-heading);">
        {{ $fields['title'] ?? 'Title' }}
    </h3>
</div>
```

Use Tailwind for: spacing, layout, borders, shadows, responsive breakpoints, typography sizing.
Use CSS variables for: colors, fonts, brand-specific styling.


## Site-Wide vs. Page-Specific CSS

When building or converting pages, it's crucial to distinguish between styles that should apply globally versus styles that are unique to a single page.

- **Site-Wide CSS:** These are the foundational styles that define the overall look and feel of the website. This includes the theme variables (`--color-primary`, etc.), base styles for HTML tags (`body`, `h1`, `p`), and common utility classes. This CSS should be applied using the `update_css(scope: 'global', ...)` tool.

- **Page-Specific CSS:** These are styles that apply only to a single page, often for a unique component or layout that doesn't appear anywhere else. This CSS should be applied using `update_css(scope: 'page', slug: '...', ...)`. The master layout file (`app.blade.php`) automatically includes a `@stack('styles')` directive, which is where page-specific CSS is injected.

When converting a static HTML file, you must classify the extracted CSS into these two buckets. The `html-to-cms-conversion` module provides a detailed workflow for this process.
