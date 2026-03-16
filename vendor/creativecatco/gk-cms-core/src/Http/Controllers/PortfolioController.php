<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use CreativeCatCo\GkCmsCore\Models\Portfolio;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Services\SeoService;

class PortfolioController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display the portfolio archive grid.
     */
    public function index(): View
    {
        $perPage = config('cms.portfolio_per_page', 12);

        $portfolios = Portfolio::published()
            ->ordered()
            ->with('categories')
            ->paginate($perPage);

        // Get all categories used by portfolios for filtering
        $categories = \CreativeCatCo\GkCmsCore\Models\Category::whereHas('portfolios')
            ->orderBy('name')
            ->get();

        $seo = $this->seoService->generate(null, [
            'title' => Setting::get('portfolio_page_title', 'Portfolio'),
            'description' => Setting::get('portfolio_page_description', 'View our portfolio of work'),
        ]);

        return view('cms-core::pages.default-portfolio-archive', [
            'portfolios' => $portfolios,
            'categories' => $categories,
            'seo' => $seo,
            'page' => null,
            'fields' => [],
        ]);
    }

    /**
     * Display a single portfolio item.
     */
    public function show(string $slug): View
    {
        $portfolio = Portfolio::published()
            ->with('categories')
            ->where('slug', $slug)
            ->firstOrFail();

        $seo = $this->seoService->generate(null, [
            'title' => $portfolio->seo_title ?: $portfolio->title,
            'description' => $portfolio->seo_description ?: $portfolio->excerpt,
            'image' => $portfolio->featured_image_url,
        ]);

        // Get related portfolios (same categories)
        $related = Portfolio::published()
            ->where('id', '!=', $portfolio->id)
            ->limit(3)
            ->ordered()
            ->get();

        return view('cms-core::pages.default-portfolio-single', [
            'portfolio' => $portfolio,
            'related' => $related,
            'seo' => $seo,
            'page' => null,
            'fields' => [],
        ]);
    }
}
