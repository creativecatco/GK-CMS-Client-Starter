<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Product;
use Illuminate\Support\Str;

class CreateProductTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_product';
    }

    public function description(): string
    {
        return 'Create a new product. Products are displayed on the products page and have their own detail pages. This is for showcasing products (not e-commerce checkout).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The product name.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Detailed product description. Supports HTML.',
                ],
                'excerpt' => [
                    'type' => 'string',
                    'description' => 'A short summary for product listings.',
                ],
                'price' => [
                    'type' => 'number',
                    'description' => 'The product price (e.g., 29.99).',
                ],
                'sale_price' => [
                    'type' => 'number',
                    'description' => 'The sale price if the product is on sale.',
                ],
                'sku' => [
                    'type' => 'string',
                    'description' => 'Product SKU/identifier.',
                ],
                'product_url' => [
                    'type' => 'string',
                    'description' => 'External URL where the product can be purchased (e.g., Amazon, Shopify link).',
                ],
                'featured_image' => [
                    'type' => 'string',
                    'description' => 'URL or storage path for the product image.',
                ],
                'gallery' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Array of image URLs or storage paths for the product gallery.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['published', 'draft'],
                    'description' => 'The product status. Default is "published".',
                ],
            ],
            'required' => ['title', 'content'],
        ];
    }

    public function execute(array $params): array
    {
        $title = $params['title'];
        $status = $params['status'] ?? 'published';

        try {
            $product = Product::create([
                'title' => $title,
                'content' => $params['content'],
                'excerpt' => $params['excerpt'] ?? Str::limit(strip_tags($params['content']), 160),
                'price' => $params['price'] ?? null,
                'sale_price' => $params['sale_price'] ?? null,
                'sku' => $params['sku'] ?? null,
                'product_url' => $params['product_url'] ?? null,
                'featured_image' => $params['featured_image'] ?? null,
                'gallery' => $params['gallery'] ?? [],
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
                'author_id' => auth()->id() ?? 1,
            ]);

            return $this->success([
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'url' => '/products/' . $product->slug,
                'price' => $product->price,
                'status' => $product->status,
            ], "Product '{$title}' created successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to create product: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        return ['title' => $params['title'] ?? '', 'action' => 'create'];
    }

    public function rollback(array $rollbackData): bool
    {
        if (($rollbackData['action'] ?? '') !== 'create') {
            return false;
        }

        $product = Product::where('title', $rollbackData['title'])->latest()->first();
        if ($product) {
            $product->delete();
            return true;
        }
        return false;
    }
}
