<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use CreativeCatCo\GkCmsCore\Models\Product;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Services\SeoService;

class ProductController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display the products archive grid.
     */
    public function index(): View
    {
        $perPage = config('cms.products_per_page', 12);

        $products = Product::published()
            ->ordered()
            ->with('categories')
            ->paginate($perPage);

        $categories = \CreativeCatCo\GkCmsCore\Models\Category::whereHas('products')
            ->orderBy('name')
            ->get();

        $seo = $this->seoService->generate(null, [
            'title' => Setting::get('products_page_title', 'Products'),
            'description' => Setting::get('products_page_description', 'Browse our products'),
        ]);

        return view('cms-core::pages.default-products-archive', [
            'products' => $products,
            'categories' => $categories,
            'seo' => $seo,
            'page' => null,
            'fields' => [],
        ]);
    }

    /**
     * Display a single product.
     */
    public function show(string $slug): View
    {
        $product = Product::published()
            ->with('categories')
            ->where('slug', $slug)
            ->firstOrFail();

        $seo = $this->seoService->generate(null, [
            'title' => $product->seo_title ?: $product->title,
            'description' => $product->seo_description ?: $product->excerpt,
            'image' => $product->featured_image_url,
        ]);

        $related = Product::published()
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->ordered()
            ->get();

        return view('cms-core::pages.default-product-single', [
            'product' => $product,
            'related' => $related,
            'seo' => $seo,
            'page' => null,
            'fields' => [],
        ]);
    }
}
