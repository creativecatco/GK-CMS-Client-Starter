# SEO Best Practices

## Required for Every Page

When creating or updating a page, ALWAYS set:

| Field | Guideline |
|-------|-----------|
| `seo_title` | 50-60 characters. Include primary keyword. Format: "Page Topic - Brand Name" |
| `seo_description` | 150-160 characters. Compelling summary with call to action. Include primary keyword. |

Set these via `create_page` parameters or `update_page_fields`:
```json
{"seo_title": "Professional Web Design Services - Company Name", "seo_description": "Transform your online presence with our professional web design services. Custom solutions for businesses of all sizes. Get a free consultation today."}
```

## Heading Hierarchy

- **One `<h1>` per page** — Usually the hero heading. This is the most important heading for SEO.
- **`<h2>`** for section headings
- **`<h3>`** for sub-section headings or card titles
- **Never skip levels** (e.g., h1 → h3 without h2)

## Image SEO

- **Always provide `alt` text** for every image
- Alt text should be descriptive: "Team of web developers collaborating in modern office" not "image1"
- Include relevant keywords naturally
- Set alt text via the `alt_text` parameter in `generate_image` or as a separate field

## Using suggest_seo Tool

The `suggest_seo` tool analyzes a page and suggests improvements:

```
suggest_seo(slug: "home")
```

It checks:
- Title tag length and content
- Meta description length and content
- Heading hierarchy
- Image alt text
- Content length
- Keyword usage

Use this after building a page to catch SEO issues.

## URL Structure

The CMS uses clean URLs:
- `/` — Home page (set via `home_page_id`)
- `/{slug}` — Regular pages
- `/blog/{slug}` — Blog posts
- `/portfolio/{slug}` — Portfolio items
- `/products/{slug}` — Products

Use descriptive, keyword-rich slugs: `web-design-services` not `page-1`.

## Content Guidelines

- Aim for 300+ words per page for SEO value
- Use the primary keyword in the h1, first paragraph, and at least one h2
- Include internal links between pages where relevant
- Use structured content (headings, lists, short paragraphs) for readability
