# CMS Core — Agency CMS Package for Laravel

A private Laravel Composer package that provides shared CMS functionality for agency client websites. Built with **Laravel 11+**, **Filament v5**, **Livewire 3**, **Tailwind CSS**, and **Alpine.js**.

---

## Features

- **Page Management** — Create and manage static pages with drag-and-drop reordering
- **Blog System** — Full blog with categories, tags, authors, and pagination
- **Media Library** — Upload, organize, and manage media files with folder support
- **Site Settings** — Grouped settings for general info, contact, social links, and analytics
- **User Roles** — Admin, Editor, and Viewer roles with Filament-based user management
- **SEO Engine** — Automatic meta tags, Open Graph, Twitter Cards, and JSON-LD structured data
- **XML Sitemap** — Auto-generated sitemap from published pages and posts
- **Filament Admin Panel** — Full admin dashboard with stats, recent activity, and quick actions
- **Theme Support** — Publishable views that client sites can override with custom themes

---

## Requirements

| Requirement     | Version  |
|-----------------|----------|
| PHP             | >= 8.2   |
| Laravel         | >= 11.0  |
| Filament        | >= 5.0   |
| Livewire        | >= 3.0   |
| MySQL           | >= 8.0   |
| Node.js         | >= 18.0  |

---

## Installation

### 1. Add the Package Repository

Since this is a private package, add the repository to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../cms-core"
        }
    ]
}
```

Or, if hosted on a private Git repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:creativecatco/gk-cms-core.git"
        }
    ]
}
```

### 2. Require the Package

```bash
composer require creativecatco/gk-cms-core
```

### 3. Publish Configuration

```bash
php artisan vendor:publish --tag=cms-config
```

This publishes `config/cms.php` where you can customize:
- Site name
- Admin panel path
- Posts per page
- Media upload settings
- Allowed file types
- User roles

### 4. Publish and Run Migrations

```bash
php artisan vendor:publish --tag=cms-migrations
php artisan migrate
```

This creates the following tables:
- `pages`
- `posts`
- `categories`
- `post_category` (pivot)
- `tags`
- `post_tag` (pivot)
- `media`
- `settings`

### 5. Publish Views (Optional)

To customize the default Blade templates:

```bash
php artisan vendor:publish --tag=cms-views
```

Views will be published to `resources/views/vendor/cms-core/`.

### 6. Register the Filament Panel Provider

Add the CMS panel provider to your `config/app.php` providers array (or it will be auto-discovered):

```php
'providers' => [
    // ...
    CreativeCatCo\GkCmsCore\CmsPanelProvider::class,
],
```

### 7. Add Role Column to Users Table

The package expects a `role` column on your `users` table. Create a migration:

```bash
php artisan make:migration add_role_to_users_table
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('editor')->after('email');
    });
}
```

### 8. Link Storage

```bash
php artisan storage:link
```

---

## Configuration

After publishing, edit `config/cms.php`:

```php
return [
    'site_name'        => env('CMS_SITE_NAME', 'My Website'),
    'admin_path'       => env('CMS_ADMIN_PATH', '/admin'),
    'posts_per_page'   => env('CMS_POSTS_PER_PAGE', 12),
    'media_upload_path'=> env('CMS_MEDIA_UPLOAD_PATH', 'cms/media'),
    'media_disk'       => env('CMS_MEDIA_DISK', 'public'),
    'max_upload_size'  => env('CMS_MAX_UPLOAD_SIZE', 10240),
    'theme'            => env('CMS_THEME', 'theme'),
    'route_prefix'     => env('CMS_ROUTE_PREFIX', ''),
    // ...
];
```

You can also set these values via `.env`:

```env
CMS_SITE_NAME="Client Website"
CMS_ADMIN_PATH=/admin
CMS_POSTS_PER_PAGE=12
CMS_THEME=theme
```

---

## Theming

The package resolves public-facing views using the configured theme namespace. To create a custom theme:

### 1. Create Theme Views

```
resources/views/theme/
├── pages/
│   ├── default.blade.php      # Default page template
│   ├── home.blade.php         # Homepage (slug: "home")
│   ├── about.blade.php        # About page (slug: "about")
│   └── contact.blade.php      # Contact page (slug: "contact")
└── blog/
    ├── index.blade.php        # Blog listing
    └── show.blade.php         # Single post
```

### 2. View Resolution Order

For pages, the controller resolves views in this order:
1. `{theme}.pages.{slug}` — Slug-specific template
2. `{theme}.pages.default` — Fallback default template

### 3. Available Variables

**Page views** receive:
- `$page` — The `Page` Eloquent model
- `$seo` — Array of SEO data (title, meta, OG, Twitter, JSON-LD)

**Blog index** receives:
- `$posts` — Paginated collection of `Post` models
- `$seo` — SEO data array

**Blog show** receives:
- `$post` — The `Post` Eloquent model (with author, categories, tags)
- `$seo` — SEO data array
- `$relatedPosts` — Collection of related posts

### 4. Using the Base Layout

Extend the package's base layout in your theme views:

```blade
@extends('cms-core::layouts.app')

@section('content')
    {{-- Your content here --}}
@endsection
```

The base layout automatically handles:
- Dynamic `<title>` tag
- Meta description
- Canonical URL
- Open Graph tags
- Twitter Card tags
- JSON-LD structured data (Organization, BreadcrumbList, Article)
- Google Analytics integration
- Favicon

---

## Admin Panel

Access the admin panel at the configured path (default: `/admin`).

### Dashboard

The dashboard includes:
- **Stats Overview** — Total pages, posts, and media items
- **Recent Activity** — Latest updated posts
- **Quick Actions** — Buttons to create new pages, posts, or upload media

### Resources

| Resource       | Features                                                                 |
|----------------|--------------------------------------------------------------------------|
| Pages          | Rich text editor, image upload, SEO fieldset, drag-and-drop reorder      |
| Posts          | Rich text editor, categories, tags, author, publish date, SEO fieldset   |
| Media          | Grid layout, file upload, image preview, alt text, folder filtering      |
| Users          | Role management (admin/editor/viewer), password management               |
| Settings       | Grouped tabs: General, Contact, Social, Analytics                        |

---

## SEO Service

The `SeoService` class can be used directly in your controllers or views:

```php
use CreativeCatCo\GkCmsCore\Services\SeoService;

$seoService = app(SeoService::class);

// Generate SEO data for a page
$seo = $seoService->generate($page);

// Generate SEO data for a post
$seo = $seoService->generate($post);

// Generate default SEO data
$seo = $seoService->generate();
```

The returned array includes:
- `title` — Full page title with site name
- `meta_description` — Meta description
- `canonical_url` — Canonical URL
- `og` — Open Graph data (title, description, image, url, type, site_name)
- `twitter` — Twitter Card data (card, title, description, image)
- `json_ld` — Array of JSON-LD schemas (Organization, BreadcrumbList, Article)

---

## Settings Helper

Use the `Setting` model to get/set site settings:

```php
use CreativeCatCo\GkCmsCore\Models\Setting;

// Get a setting
$siteName = Setting::get('site_name', 'Default');

// Set a setting
Setting::set('site_name', 'New Name', 'general');

// Get all settings in a group
$social = Setting::getGroup('social');

// Flush cached settings
Setting::flushCache();
```

---

## Routes

The package registers the following public routes:

| Method | URI              | Name            | Controller                   |
|--------|------------------|-----------------|------------------------------|
| GET    | `/sitemap.xml`   | cms.sitemap     | SitemapController@index      |
| GET    | `/blog`          | cms.blog.index  | PostController@index         |
| GET    | `/blog/{slug}`   | cms.blog.show   | PostController@show          |
| GET    | `/{slug}`        | cms.page.show   | PageController@show          |

The page catch-all route excludes `admin`, `livewire`, and `filament` prefixes.

---

## Package Structure

```
cms-core/
├── composer.json
├── README.md
├── config/
│   └── cms.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_pages_table.php
│       ├── 2024_01_01_000002_create_posts_table.php
│       ├── 2024_01_01_000003_create_categories_table.php
│       ├── 2024_01_01_000004_create_post_category_table.php
│       ├── 2024_01_01_000005_create_tags_table.php
│       ├── 2024_01_01_000006_create_post_tag_table.php
│       ├── 2024_01_01_000007_create_media_table.php
│       └── 2024_01_01_000008_create_settings_table.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── components/
│       │   ├── header.blade.php
│       │   └── footer.blade.php
│       ├── pages/
│       │   └── default.blade.php
│       ├── blog/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── errors/
│       │   └── 404.blade.php
│       └── filament/
│           ├── pages/
│           │   └── settings.blade.php
│           └── widgets/
│               └── quick-actions.blade.php
├── routes/
│   └── web.php
└── src/
    ├── CmsCoreServiceProvider.php
    ├── CmsPanelProvider.php
    ├── Models/
    │   ├── Page.php
    │   ├── Post.php
    │   ├── Category.php
    │   ├── Tag.php
    │   ├── Media.php
    │   └── Setting.php
    ├── Http/
    │   └── Controllers/
    │       ├── PageController.php
    │       ├── PostController.php
    │       └── SitemapController.php
    ├── Services/
    │   └── SeoService.php
    └── Filament/
        ├── Resources/
        │   ├── PageResource.php
        │   ├── PageResource/
        │   │   └── Pages/
        │   │       ├── ListPages.php
        │   │       ├── CreatePage.php
        │   │       └── EditPage.php
        │   ├── PostResource.php
        │   ├── PostResource/
        │   │   └── Pages/
        │   │       ├── ListPosts.php
        │   │       ├── CreatePost.php
        │   │       └── EditPost.php
        │   ├── MediaResource.php
        │   ├── MediaResource/
        │   │   └── Pages/
        │   │       ├── ListMedia.php
        │   │       ├── CreateMedia.php
        │   │       └── EditMedia.php
        │   ├── UserResource.php
        │   └── UserResource/
        │       └── Pages/
        │           ├── ListUsers.php
        │           ├── CreateUser.php
        │           └── EditUser.php
        ├── Pages/
        │   └── SettingPage.php
        └── Widgets/
            ├── StatsOverviewWidget.php
            ├── RecentActivityWidget.php
            └── QuickActionsWidget.php
```

---

## License

This package is proprietary software. Unauthorized distribution is prohibited.
