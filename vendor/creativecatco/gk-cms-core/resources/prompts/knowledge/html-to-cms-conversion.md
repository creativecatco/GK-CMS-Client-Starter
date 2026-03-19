# HTML File Import — Converting Static HTML to CMS Pages

## The Golden Rule

When a user uploads an HTML file and asks you to replicate it as a CMS page, **ALWAYS use the `import_html_page` tool**. Do NOT attempt to manually recreate the HTML by reading it and calling `create_page` or `update_page_template` — that approach is unreliable and will produce pages that look nothing like the original.

The `import_html_page` tool is a dedicated, programmatic converter that:
1. Extracts ALL CSS from `<style>` tags and saves it as page-specific CSS
2. Extracts the `<body>` HTML and preserves the exact structure and class names
3. Auto-injects `data-field` attributes on editable elements (headings, paragraphs, buttons, images)
4. Creates the page with all fields populated from the original content
5. Handles HTML entity decoding automatically (e.g., `&mdash;` → `—`, `&#128273;` → `🔑`)

This produces a **pixel-perfect** replica of the original HTML, with full CMS editability.

## How to Use It

### Step 1: Identify the Storage Path

When a user uploads an HTML file, the system automatically saves it to disk and provides the storage path in the file context. Look for:

```
[HTML FILE SAVED FOR IMPORT]
Storage path: /path/to/storage/app/html-imports/1234567890_filename.html
```

### Step 2: Call the Tool

```
import_html_page(
    storage_path: "/path/to/storage/app/html-imports/1234567890_filename.html",
    title: "Page Title",
    slug: "page-slug"
)
```

That's it. One tool call. The tool handles everything else.

### Step 3: Verify and Report

After the import succeeds:
1. Tell the user the page was created and provide the URL (e.g., `/page-slug`)
2. Ask them to review it and let you know if any adjustments are needed
3. If they want text changes, use `update_page_fields` to modify specific field values
4. If they want style changes, use `update_css` with `scope: page` to adjust the page CSS

## Handling External Resources

The import tool preserves all CSS and HTML structure, but it does NOT automatically download external resources like:
- Images hosted on external URLs (e.g., `https://example.com/img/hero.jpg`)
- Linked stylesheets (`<link rel="stylesheet" href="...">`)
- External fonts (Google Fonts, etc.)

After the import, if images are broken:
1. Use `upload_image` to download external images into the media library
2. Use `update_page_fields` to update the image field values with the new local paths

If external stylesheets are needed:
1. The user may need to provide those CSS files separately
2. Or you can add `<link>` tags to the template via `update_page_template`

## When NOT to Use This Tool

- When the user wants a **new page designed from scratch** → use `create_page` instead
- When the user describes a page concept in words → use `create_page` instead
- When the user wants to **edit an existing page** → use `update_page_fields`, `update_css`, or `patch_page_template`

Only use `import_html_page` when the user provides an actual HTML file to replicate.

## Post-Import: Establishing Site-Wide Styles

After a successful import, if the user wants to use the imported page's design as the basis for the whole site:

1. **Extract brand colors** from the page CSS and update the theme via `update_theme`
2. **Extract common styles** (fonts, base element styles, utility classes) and save them as global CSS via `update_css(scope: 'global')`
3. This way, new pages built later will automatically inherit the same design language

Only do this if the user explicitly asks to apply the styles site-wide. Do not automatically modify global styles during an import.
