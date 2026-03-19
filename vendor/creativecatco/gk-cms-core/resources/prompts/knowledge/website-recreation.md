# Website Recreation

## Use Cases

- User provides a URL and says "make my site look like this"
- User provides a static HTML file to convert to CMS pages
- User wants to migrate content from an existing website
- User wants to match a competitor's design style

## Workflow: Recreating from a URL

### Step 1: Scan the source

```
scan_website(url: "https://example.com", include_subpages: true)
```

This returns:
- Page title, meta description, keywords
- Headings (h1-h4) with hierarchy
- Text content (up to 5000 chars)
- Navigation structure (labels + hrefs)
- Images (src + alt, up to 20)
- Color palette (extracted from CSS)
- Font families
- Social links
- Contact information
- Subpage content (if include_subpages is true)

### Step 2: Extract the design system

From the scan results:
1. **Colors** — Map the extracted colors to theme variables:
   - Most prominent brand color → `theme_primary_color`
   - Dark/contrast color → `theme_secondary_color`
   - Accent/link color → `theme_accent_color`
   - Body text color → `theme_text_color`
   - Background color → `theme_bg_color`
2. **Fonts** — Map to `theme_font_heading` and `theme_font_body`
3. **Apply** via `update_theme`

### Step 3: Plan the page structure

Map the scanned content to CMS pages:
- Each major navigation item = one page
- Identify page types (home, about, services, contact, etc.)
- Map headings and content to appropriate sections
- Identify which images to download/recreate

### Step 4: Handle images

For each important image from the scan:
1. **Download** via `upload_image(url: "https://example.com/image.jpg")` — only if the image is royalty-free or owned by the user
2. **Recreate** via `generate_image` if the original can't be used (copyright, low quality)
3. Note the returned path for use in page fields

### Step 5: Build pages

Create each page using `create_page` with:
- Content mapped from the scanned text
- Images placed in appropriate fields
- Navigation structure matching the original
- SEO titles and descriptions from the scan

### Step 6: Set up navigation

Use `update_menu` to create the navigation structure matching the scanned site.

### Step 7: Verify

Use `render_page` on each created page to verify the result.

## Workflow: Converting Static HTML (DEPRECATED)

**IMPORTANT:** This workflow is deprecated. When the user provides a static HTML file, you MUST use the `html-to-cms-conversion` knowledge module instead. It provides a more precise, high-fidelity workflow that preserves the original CSS and structure.

When the user provides a static HTML file or paste:

### Step 1: Analyze the HTML

Identify:
- The page structure (sections, layout)
- Content that should be editable (headings, text, images)
- Colors and fonts used
- Interactive elements (buttons, forms)

### Step 2: Map to CMS concepts

| Static HTML Element | CMS Equivalent |
|--------------------|----------------|
| `<h1>`, `<h2>`, etc. | `data-field-type="text"` |
| `<p>`, `<span>` text | `data-field-type="text"` or `"textarea"` |
| Rich HTML blocks | `data-field-type="richtext"` |
| `<img>` tags | `data-field-type="image"` |
| Background images | `data-field-type="section_bg"` |
| Buttons/links | `data-field-type="button"` |
| Repeating items | `data-field-type="repeater"` |
| Hardcoded colors | Replace with CSS variables |

### Step 3: Convert

1. Replace hardcoded colors with CSS variables
2. Add `data-field` and `data-field-type` to all editable elements
3. Replace static content with `{{ $fields['key'] ?? 'default' }}`
4. Convert repeating patterns to `@foreach` loops with repeater fields
5. Remove `<html>`, `<head>`, `<body>` wrappers (layout provides these)
6. Remove external CSS/JS includes that Tailwind replaces

### Step 4: Create the page

Use `create_page` with the converted template and extracted field values.

## What to Preserve vs Adapt

| Preserve | Adapt |
|----------|-------|
| Content (text, headings) | Colors → CSS variables |
| Page structure (sections) | Fonts → theme fonts |
| Navigation labels | External CSS → Tailwind classes |
| SEO meta tags | Static images → CMS media |
| Contact information | Hardcoded values → editable fields |
| Business branding | Framework-specific code → Blade syntax |

## Important Notes

- **Copyright:** Only download images the user owns or that are royalty-free. When in doubt, use `generate_image` to create similar images.
- **Don't copy exactly:** Adapt the design to work within the CMS. The goal is to capture the look and feel, not produce a pixel-perfect clone.
- **Simplify:** If the source site uses complex JavaScript frameworks, simplify to Tailwind + Alpine.js equivalents.
- **Ask the user:** If you're unsure whether to copy certain content or images, ask first.
