<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use CreativeCatCo\GkCmsCore\Models\Post;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Services\SeoService;

class PostController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display a paginated listing of published blog posts.
     *
     * Strategy:
     * 1. Try to load from the `posts` table (native Post model)
     * 2. Fall back to pages with page_type='post' (CMS page-based posts)
     * 3. Render using the blog page template or a theme-specific view
     */
    public function index(): View
    {
        $theme = config('cms.theme', 'theme');
        $perPage = config('cms.posts_per_page', 12);

        // Try native posts table first
        $nativePosts = null;
        try {
            if (\Schema::hasTable('posts')) {
                $nativePosts = Post::published()
                    ->orderByDesc('published_at')
                    ->paginate($perPage);
            }
        } catch (\Exception $e) {
            // Table doesn't exist or query failed
        }

        // Also get page-based posts (page_type = 'post')
        $pagePosts = Page::where('page_type', 'post')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->get();

        // Get the blog page for its fields (heading, subheading, etc.)
        $blogPage = Page::where('slug', 'blog')
            ->where('status', 'published')
            ->first();

        $fields = $blogPage->fields ?? [];
        $page = $blogPage;
        $seo = $this->seoService->generate($blogPage);

        // Determine which view to use
        $viewCandidates = [
            "{$theme}.blog.index",
            'cms-core::pages.default-blog',
        ];

        $view = 'cms-core::pages.default-blog';
        foreach ($viewCandidates as $candidate) {
            if (view()->exists($candidate)) {
                $view = $candidate;
                break;
            }
        }

        return view($view, [
            'page' => $page,
            'fields' => $fields,
            'seo' => $seo,
            'posts' => $nativePosts,
            'pagePosts' => $pagePosts,
        ]);
    }

    /**
     * Display a single blog post by slug.
     */
    public function show(string $slug): View
    {
        $theme = config('cms.theme', 'theme');

        // Try native posts table first
        $post = null;
        try {
            if (\Schema::hasTable('posts')) {
                $post = Post::published()
                    ->where('slug', $slug)
                    ->first();
            }
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        if ($post) {
            $seo = $this->seoService->generate($post);

            $relatedPosts = collect();
            try {
                $relatedPosts = Post::published()
                    ->where('id', '!=', $post->id)
                    ->limit(3)
                    ->get();
            } catch (\Exception $e) {}

            $viewCandidates = [
                "{$theme}.blog.show",
                'cms-core::pages.default-post',
            ];

            $view = 'cms-core::pages.default-post';
            foreach ($viewCandidates as $candidate) {
                if (view()->exists($candidate)) {
                    $view = $candidate;
                    break;
                }
            }

            return view($view, [
                'post' => $post,
                'seo' => $seo,
                'relatedPosts' => $relatedPosts,
                'page' => null,
                'fields' => [],
            ]);
        }

        // Fall back to page-based post
        $page = Page::where('slug', $slug)
            ->where('page_type', 'post')
            ->where('status', 'published')
            ->firstOrFail();

        $seo = $this->seoService->generate($page);
        $fields = $page->fields ?? [];

        $template = $page->template ?? 'default-post';
        $view = $this->resolveView($theme, $template, $slug);

        return view($view, [
            'page' => $page,
            'fields' => $fields,
            'seo' => $seo,
            'post' => null,
            'relatedPosts' => collect(),
        ]);
    }

    /**
     * Resolve the Blade view path with fallback chain.
     */
    protected function resolveView(string $theme, string $template, ?string $slug = null): string
    {
        $candidates = [
            "{$theme}.pages.{$template}",
            "cms-core::pages.{$template}",
            "{$theme}.pages.default-post",
            "cms-core::pages.default-post",
        ];

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        return 'cms-core::pages.default-post';
    }
}
