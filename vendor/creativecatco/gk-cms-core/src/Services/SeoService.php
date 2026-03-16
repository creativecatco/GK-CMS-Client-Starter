<?php

namespace CreativeCatCo\GkCmsCore\Services;

use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Setting;
use Illuminate\Database\Eloquent\Model;

class SeoService
{
    /**
     * Generate complete SEO data for a given model or defaults.
     */
    public function generate(?Model $model = null): array
    {
        $siteName = Setting::get('site_name', config('cms.site_name', 'My Website'));

        if ($model instanceof Page) {
            return $this->generateForPage($model, $siteName);
        }

        if ($model instanceof Post) {
            return $this->generateForPost($model, $siteName);
        }

        return $this->generateDefaults($siteName);
    }

    /**
     * Generate SEO data for a Page model.
     */
    protected function generateForPage(Page $page, string $siteName): array
    {
        $title = $page->seo_title ?: $page->title;
        $description = $page->seo_description ?: '';
        $ogImage = $page->og_image_url;
        $homePageId = Setting::get('home_page_id');
        $isHomePage = ($homePageId && $page->id == $homePageId) || (!$homePageId && $page->slug === 'home');
        $canonicalUrl = url($isHomePage ? '/' : $page->slug);

        return [
            'title' => $title . ' | ' . $siteName,
            'meta_description' => $description,
            'canonical_url' => $canonicalUrl,
            'og' => [
                'title' => $title,
                'description' => $description,
                'image' => $ogImage ? url($ogImage) : null,
                'url' => $canonicalUrl,
                'type' => 'website',
                'site_name' => $siteName,
            ],
            'twitter' => [
                'card' => $ogImage ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $ogImage ? url($ogImage) : null,
            ],
            'json_ld' => array_filter([
                $this->organizationSchema($siteName),
                $this->breadcrumbSchema([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => $page->title, 'url' => $canonicalUrl],
                ]),
            ]),
        ];
    }

    /**
     * Generate SEO data for a Post model.
     */
    protected function generateForPost(Post $post, string $siteName): array
    {
        $title = $post->seo_title ?: $post->title;
        $description = $post->seo_description ?: $post->excerpt ?: '';
        $ogImage = $post->og_image_url;
        $canonicalUrl = url('blog/' . $post->slug);

        return [
            'title' => $title . ' | ' . $siteName,
            'meta_description' => $description,
            'canonical_url' => $canonicalUrl,
            'og' => [
                'title' => $title,
                'description' => $description,
                'image' => $ogImage ? url($ogImage) : null,
                'url' => $canonicalUrl,
                'type' => 'article',
                'site_name' => $siteName,
            ],
            'twitter' => [
                'card' => $ogImage ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $ogImage ? url($ogImage) : null,
            ],
            'json_ld' => array_filter([
                $this->organizationSchema($siteName),
                $this->breadcrumbSchema([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Blog', 'url' => url('blog')],
                    ['name' => $post->title, 'url' => $canonicalUrl],
                ]),
                $this->articleSchema($post, $siteName, $canonicalUrl, $ogImage),
            ]),
        ];
    }

    /**
     * Public method to generate default SEO data.
     */
    public function generateDefault(): array
    {
        $siteName = Setting::get('site_name', config('cms.site_name', 'My Website'));
        return $this->generateDefaults($siteName);
    }

    /**
     * Generate default SEO data (for index/listing pages).
     */
    protected function generateDefaults(string $siteName): array
    {
        $tagline = Setting::get('tagline', '');
        $canonicalUrl = url('/');

        return [
            'title' => $siteName . ($tagline ? ' — ' . $tagline : ''),
            'meta_description' => $tagline,
            'canonical_url' => $canonicalUrl,
            'og' => [
                'title' => $siteName,
                'description' => $tagline,
                'image' => null,
                'url' => $canonicalUrl,
                'type' => 'website',
                'site_name' => $siteName,
            ],
            'twitter' => [
                'card' => 'summary',
                'title' => $siteName,
                'description' => $tagline,
                'image' => null,
            ],
            'json_ld' => [
                $this->organizationSchema($siteName),
            ],
        ];
    }

    /**
     * Generate Organization JSON-LD schema.
     */
    protected function organizationSchema(string $siteName): array
    {
        $logo = Setting::get('logo');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $logo ? url($logo) : null,
            'contactPoint' => array_filter([
                '@type' => 'ContactPoint',
                'email' => Setting::get('contact_email'),
                'telephone' => Setting::get('contact_phone'),
            ]),
            'sameAs' => array_values(array_filter([
                Setting::get('social_facebook'),
                Setting::get('social_twitter'),
                Setting::get('social_instagram'),
                Setting::get('social_linkedin'),
            ])),
        ]);
    }

    /**
     * Generate BreadcrumbList JSON-LD schema.
     */
    protected function breadcrumbSchema(array $items): array
    {
        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Generate Article JSON-LD schema for blog posts.
     */
    protected function articleSchema(Post $post, string $siteName, string $url, ?string $image): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $image ? url($image) : null,
            'url' => $url,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? 'Unknown',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => Setting::get('logo') ? url(Setting::get('logo')) : null,
                ],
            ],
        ]);
    }
}
