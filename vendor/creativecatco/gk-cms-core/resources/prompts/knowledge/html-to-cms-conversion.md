# High-Fidelity HTML to CMS Conversion

## 1. Philosophy: Preserve First, Adapt Minimally

The primary goal of this workflow is to create a **high-fidelity, 1:1 replication** of a provided static HTML file within the CMS. Unlike other workflows, you should NOT attempt to adapt, simplify, or "Tailwind-ify" the design. The objective is to preserve the original HTML structure and CSS exactly as provided, only adding the necessary CMS hooks to make the content editable.

This process is deterministic and must be followed precisely to ensure reliability.

## 2. The Step-by-Step Conversion Workflow

### Step 1: Analyze and Extract Assets

1.  Read the full source HTML file provided by the user.
2.  Using regex, extract the content of all `<style>` blocks.
3.  Using regex, extract all `<link rel="stylesheet">` URLs.
4.  For each linked stylesheet, read the content of the CSS file.
5.  Combine all extracted CSS into a single string. This is the **Total CSS**.
6.  Using regex, extract all `<img>` tags and their `src` attributes. Keep a list of these image URLs.

### Step 2: Classify CSS into Site-Wide vs. Page-Specific

This is the most critical analysis step. You must programmatically loop through the **Total CSS** and separate rules into two buckets.

| CSS Type | Heuristics for Identification |
| :--- | :--- |
| **Site-Wide CSS** | - Rules targeting `:root` or `body` (e.g., `font-family`, `color`, `background-color`).<br>- Generic HTML tag styles (e.g., `a`, `p`, `h1`, `h2`).<br>- Font-face definitions (`@font-face`).<br>- Definitions of CSS variables (`--variable-name: value;`).<br>- Widely used utility classes that appear frequently in the HTML body. |
| **Page-Specific CSS** | - Rules targeting specific IDs (e.g., `#hero-section`, `#contact-form`).<br>- Complex component styles that are clearly tied to a single structural element.<br>- Keyframe animations (`@keyframes`).<br>- Styles for pseudo-elements (`::before`, `::after`) on specific components. |

### Step 3: Establish the Site-Wide Design System

1.  **Extract Colors & Fonts:** From the **Site-Wide CSS**, identify the primary brand colors and the main heading/body fonts.
2.  **Update Theme:** Call `update_theme` to set the core CSS variables (`theme_primary_color`, `theme_font_heading`, etc.) based on your analysis. This ensures that new pages created later will have a consistent base.
3.  **Set Global CSS:** Take the remaining **Site-Wide CSS** rules (generic tag styles, utilities, etc.) and save them using `update_css(scope: 'global', css: '...')`. This makes the core styles available to every page on the site.

### Step 4: Migrate Images

1.  Iterate through the list of image URLs collected in Step 1.
2.  For each URL, call `upload_image(url: '...')` to download it into the CMS media library.
3.  Create a key-value map that links the original image URL to the new, storage-relative path returned by the tool (e.g., `{'https://example.com/img/hero.png': 'media/uploads/hero.png'}`).

### Step 5: Convert HTML to a Blade Template

1.  Start with the full original HTML source code.
2.  **Strip Wrappers:** Remove the `<html>`, `<head>`, and `<body>` tags, keeping only the content that was inside the `<body>`.
3.  **Replace Image Paths:** Search through the HTML content and replace every original image `src` with its corresponding new path from the map created in Step 4.
4.  **Identify & Hook Editable Content:**
    *   For every piece of text (headings, paragraphs, list items) that should be editable, add a `data-field="unique_field_key"` and `data-field-type="text_or_textarea"` attribute to its containing element.
    *   For every `<img>` tag, add `data-field="image_field_key"` and `data-field-type="image"`.
    *   For buttons or links that should be editable, add `data-field="button_key"` and `data-field-type="button"`.
5.  **Extract Field Values & Replace with Blade:**
    *   As you identify editable content, extract the static value (the text, the image path, the button text/link) into a JSON object for the `fields` parameter.
    *   **CRITICAL:** Before adding text to the `fields` object, you **MUST** decode any HTML entities to their actual Unicode characters (e.g., `&mdash;` becomes `—`, `&#128273;` becomes `🔑`).
    *   In the template, replace the static content you just extracted with the appropriate Blade directive (e.g., `{{ $fields['unique_field_key'] }}`). Use `{!! !!}` for any fields that contain HTML.

### Step 6: Create the Page & Apply Styles

1.  Call `create_page` with the following parameters:
    *   `title`: A suitable title for the page.
    *   `template_code`: The full Blade template string you constructed in Step 5.
    *   `fields`: The JSON object containing all the extracted and decoded field values.
2.  Immediately after the page is created, call `update_css` with:
    *   `scope: 'page'`
    *   `slug`: The slug of the page you just created.
    *   `css`: The **Page-Specific CSS** you identified in Step 2.

### Step 7: Verify

1.  Call `render_page` (or `render_page_visually` if available) on the newly created page.
2.  Inspect the output to ensure the page renders without errors and that the content appears correctly.
3.  Inform the user that the process is complete and ask them to review the live page.
