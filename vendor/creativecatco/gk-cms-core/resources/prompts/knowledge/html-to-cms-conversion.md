# HTML File Import — Converting Static HTML to CMS Pages

## The Golden Rule

When a user uploads an HTML file and asks you to replicate it as a CMS page, **ALWAYS use the `import_html_page` tool**. Do NOT attempt to manually recreate the HTML by reading it and calling `create_page` or `update_page_template` — that approach is unreliable and will produce pages that look nothing like the original.

The `import_html_page` tool is a dedicated, programmatic converter that:
1. Extracts ALL CSS from `<style>` tags, scopes it to prevent conflicts with the CMS layout
2. Detects and **always strips** `<header>` and `<footer>` tags from the page body
3. Extracts the main body HTML and preserves the exact structure and class names
4. Auto-injects `data-field` attributes on editable elements (headings, paragraphs, buttons, images)
5. Creates the page with all fields populated from the original content
6. Handles HTML entity decoding automatically (e.g., `&mdash;` → `—`, `&#128273;` → `🔑`)

This produces a **pixel-perfect** replica of the original HTML, with full CMS editability.

## How to Use It

### Step 1: Identify the Storage Path

When a user uploads an HTML file, the system automatically saves it to disk and provides the storage path in the file context. Look for:

```
[HTML FILE SAVED FOR IMPORT]
Storage path: /path/to/storage/app/html-imports/1234567890_filename.html
```

### Step 2: Call the Tool (Page Import)

```
import_html_page(
    storage_path: "/path/to/storage/app/html-imports/1234567890_filename.html",
    title: "Page Title",
    slug: "page-slug"
)
```

That's it. One tool call. The tool handles everything else.

**IMPORTANT:** Do NOT set `import_header` or `import_footer` to true on this first call. The tool will always strip the header/footer from the page body regardless, and will report whether they were found.

### Step 3: Ask About Header/Footer

After the import succeeds, the tool will report whether the HTML contained a `<header>` and/or `<footer>`. You **MUST** then ask the user:

> "The page has been created successfully! I noticed the HTML file also contained a [header/footer/header and footer]. Would you like me to import [it/them] as your site-wide header and footer? (If you've already set up a header/footer, or plan to import more pages with the same header, you can skip this.)"

### Step 4: Import Header/Footer (If User Says Yes)

If the user confirms, call the tool again with **only** the storage path and the import flags:

```
import_html_page(
    storage_path: "/path/to/storage/app/html-imports/1234567890_filename.html",
    import_header: true,
    import_footer: true
)
```

Note: No `title` or `slug` needed — this mode only creates/updates the header and footer CMS records. If a header or footer already exists, it will be **replaced** with the new one.

### Step 5: Verify and Report

After the import succeeds:
1. Tell the user the page was created and provide the URL (e.g., `/page-slug`)
2. Ask them to review it and let you know if any adjustments are needed
3. If they want text changes, use `update_page_fields` to modify specific field values
4. If they want style changes, use `update_css` with `scope: page` to adjust the page CSS

## Handling External Resources

The import tool preserves all CSS and HTML structure, but it does NOT automatically download external resources like:
- Images hosted on external URLs (e.g., `https://example.com/img/hero.jpg`)
- Linked stylesheets (`<link rel="stylesheet" href="...">`), except Google Fonts which are preserved
- External fonts beyond Google Fonts

After the import, if images are broken:
1. Use `upload_image` to download external images into the media library
2. Use `update_page_fields` to update the image field values with the new local paths

## ZIP Archive Import — Multi-Page Sites

When a user uploads a `.zip` file containing multiple HTML pages and images, **use the `import_zip_site` tool**.

The ZIP archive should contain:
- One or more `.html` / `.htm` files (pages)
- An `images/` folder (or similar) with image assets
- Optionally, external `.css` files

### How to Use It

When a user uploads a ZIP, the system saves it and provides the storage path:

```
[ZIP ARCHIVE READY FOR IMPORT]
Storage path: /path/to/storage/app/zip-imports/1234567890_site.zip
Contents: 5 HTML file(s), 23 image(s), 1 CSS file(s)
```

Call the tool:

```
import_zip_site(
    storage_path: "/path/to/storage/app/zip-imports/1234567890_site.zip"
)
```

The tool will:
1. Extract the ZIP to a temp directory
2. Import ALL images to the CMS media library
3. Rewrite `<img src>` paths in the HTML to point to the CMS media URLs
4. Merge any external `.css` files into each page's CSS
5. Process each HTML file through the same conversion pipeline as `import_html_page`
6. Extract header/footer from the FIRST HTML file only and ask about importing them

After the import, report the results to the user: how many pages were created, how many images imported, and whether any pages failed.

## When NOT to Use These Tools

- When the user wants a **new page designed from scratch** → use `create_page` instead
- When the user describes a page concept in words → use `create_page` instead
- When the user wants to **edit an existing page** → use `update_page_fields`, `update_css`, or `patch_page_template`

- Use `import_html_page` when the user provides a single HTML file
- Use `import_zip_site` when the user provides a ZIP archive with multiple pages and/or images
- Do NOT use either tool when the user wants a new page designed from scratch or wants to edit an existing page

## Post-Import: Establishing Site-Wide Styles

After a successful import, if the user wants to use the imported page's design as the basis for the whole site:

1. **Extract brand colors** from the page CSS and update the theme via `update_theme`
2. **Extract common styles** (fonts, base element styles, utility classes) and save them as global CSS via `update_css(scope: 'global')`
3. This way, new pages built later will automatically inherit the same design language

Only do this if the user explicitly asks to apply the styles site-wide. Do not automatically modify global styles during an import.
