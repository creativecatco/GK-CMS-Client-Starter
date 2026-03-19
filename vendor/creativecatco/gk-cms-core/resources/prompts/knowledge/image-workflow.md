# Image Workflow

## Core Rule

**Every page MUST have real images.** Never leave placeholder text like "image goes here" or empty `src` attributes. When building or redesigning a page, ALWAYS generate images for hero sections, backgrounds, and key visuals.

## Image Sources (Priority Order)

1. **`list_media`** — Check for existing uploaded images first
2. **`generate_image`** — Create custom AI-generated images (hero banners, backgrounds, illustrations)
3. **`upload_image`** — Download royalty-free images from the web (Unsplash, Pexels, Pixabay only)

When sourcing images from the web via `upload_image`, only use royalty-free sources and cite the source in your chat response, e.g.: "Image sourced from Unsplash (royalty-free)."

## generate_image Parameters

| Parameter | Options | When to Use |
|-----------|---------|-------------|
| `aspect_ratio` | `"16:9"`, `"1:1"`, `"4:3"`, `"3:4"`, `"9:16"` | `16:9` for hero banners, `1:1` for icons/logos |
| `style` | `"photorealistic"`, `"illustration"`, `"icon"` | Match the site's visual style |
| `prompt` | Descriptive text | Be specific about subject, mood, colors |
| `alt_text` | Descriptive text | Always provide for SEO and accessibility |

## Complete Image Placement Workflow

### Step 1: Identify the field

Call `get_page_info` for the target page. Check the `field_map` in the response to find:
- The **field key** (e.g., `hero_bg`, `hero_image`, `about_photo`)
- The **field type** (e.g., `image`, `section_bg`)

### Step 2: Generate the image

Call `generate_image` with appropriate parameters. Note the `path` in the response (e.g., `"media/ai-generated/hero-banner.png"`).

### Step 3: Update the field (format depends on field type)

**If field type is `image`:**
```json
{"hero_image": "media/ai-generated/hero-banner.png"}
```
Simple string path. That's it.

**If field type is `section_bg`:**
```json
{"hero_bg": {"image": "media/ai-generated/hero-banner.png", "mode": "cover", "color": null, "colorType": "solid", "gradient": null, "overlay": {"type": "none"}}}
```
Must be a JSON object. If the field already has values, preserve them and only change the `"image"` key. Load the `section-bg` module for full documentation.

### Step 4: Verify

Call `render_page` to confirm the image appears correctly on the page.

## Common Mistakes

| Mistake | Correct Approach |
|---------|-----------------|
| Rewriting the template to change an image | Use `update_page_fields` — NEVER rewrite the template |
| Passing a full URL as the image path | Use storage-relative path: `"media/ai-generated/file.png"` |
| Passing a string for a `section_bg` field | Must be a JSON object (auto-correction exists but use correct format) |
| Forgetting to verify after placement | Always call `render_page` after updating |
| Not providing alt_text | Always include descriptive alt_text for accessibility |

## When Building a Full Page

Generate at minimum:
- 1 hero/banner image (`16:9`, `photorealistic` or matching site style)
- Background images for any `section_bg` fields
- Relevant images for content sections (about photos, team photos, etc.)
