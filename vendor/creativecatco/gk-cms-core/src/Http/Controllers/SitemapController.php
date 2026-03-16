<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Setting;

class SitemapController extends Controller
{
    /**
     * Generate an XML sitemap from all published pages and posts.
     */
    public function index(): Response
    {
        $pages = Page::published()
            ->orderBy('updated_at', 'desc')
            ->get();

        $posts = Post::published()
            ->orderBy('updated_at', 'desc')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Homepage
        $xml .= $this->urlEntry(url('/'), now()->toW3cString(), 'daily', '1.0');

        // Pages
        foreach ($pages as $page) {
            $homePageId = Setting::get('home_page_id');
            $isHomePage = ($homePageId && $page->id == $homePageId) || (!$homePageId && $page->slug === 'home');
            if ($isHomePage) {
                continue; // Already added as homepage above
            }
            $loc = url($page->slug);
            $xml .= $this->urlEntry(
                $loc,
                $page->updated_at->toW3cString(),
                'weekly',
                '0.8'
            );
        }

        // Blog index
        $xml .= $this->urlEntry(url('blog'), now()->toW3cString(), 'daily', '0.7');

        // Posts
        foreach ($posts as $post) {
            $xml .= $this->urlEntry(
                url('blog/' . $post->slug),
                $post->updated_at->toW3cString(),
                'weekly',
                '0.6'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate a single URL entry for the sitemap.
     */
    protected function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>{$loc}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
}
