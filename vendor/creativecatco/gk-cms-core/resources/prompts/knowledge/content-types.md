# Content Types

## Pages

The primary content type. Each page has a custom Blade template and JSON fields.

| Column | Description |
|--------|-------------|
| `title` | Page title |
| `slug` | URL slug (page accessible at `/{slug}`) |
| `page_type` | `page`, `header`, or `footer` |
| `template` | Template identifier (usually `custom`) |
| `custom_template` | Full Blade template code |
| `fields` | JSON object of field key-value pairs |
| `field_definitions` | JSON array of discovered field definitions |
| `custom_css` | Page-specific CSS |
| `status` | `published` or `draft` |
| `seo_title` | SEO title tag |
| `seo_description` | SEO meta description |
| `sort_order` | Display order |

**Tools:** `create_page`, `update_page_template`, `update_page_fields`, `delete_page`, `get_page_info`, `list_pages`

## Posts (Blog)

Blog posts with rich content. Requires `enable_blog` setting to be enabled.

| Column | Description |
|--------|-------------|
| `title` | Post title |
| `slug` | URL slug (post accessible at `/blog/{slug}`) |
| `content` | HTML content body |
| `excerpt` | Short summary for listings |
| `featured_image` | Image path for the post thumbnail |
| `status` | `published` or `draft` |

**Tool:** `create_post`

## Portfolios

Portfolio/case study items. Requires `enable_portfolio` setting to be enabled.

| Column | Description |
|--------|-------------|
| `title` | Project title |
| `slug` | URL slug (accessible at `/portfolio/{slug}`) |
| `content` | HTML description of the project |
| `client` | Client name |
| `project_url` | Link to the live project |
| `gallery` | JSON array of image paths |

**Tool:** `create_portfolio`

## Products

Product listings. Requires `enable_products` setting to be enabled.

| Column | Description |
|--------|-------------|
| `title` | Product name |
| `slug` | URL slug (accessible at `/products/{slug}`) |
| `content` | HTML product description |
| `price` | Regular price |
| `sale_price` | Sale/discounted price |
| `product_url` | Link to purchase |

**Tool:** `create_product`

## URL Structure

| Content Type | URL Pattern | Example |
|-------------|-------------|---------|
| Home page | `/` | Set via `home_page_id` setting |
| Regular page | `/{slug}` | `/about`, `/services`, `/contact` |
| Blog post | `/blog/{slug}` | `/blog/getting-started` |
| Portfolio | `/portfolio/{slug}` | `/portfolio/brand-redesign` |
| Product | `/products/{slug}` | `/products/premium-plan` |

## Feature Toggles

Enable/disable content types via `update_settings`:

```json
{
    "enable_blog": "1",
    "enable_portfolio": "1",
    "enable_products": "0"
}
```

When disabled, the corresponding routes and listing pages are hidden.
