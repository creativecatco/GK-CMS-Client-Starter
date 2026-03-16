<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Post;
use Illuminate\Support\Str;

class CreatePostTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_post';
    }

    public function description(): string
    {
        return 'Create a new blog post. Posts are displayed on the blog page and have their own detail pages. Content supports HTML for rich formatting.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The post title.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'The post content. Supports HTML for formatting (headings, paragraphs, lists, images, etc.).',
                ],
                'excerpt' => [
                    'type' => 'string',
                    'description' => 'A short summary of the post (1-2 sentences). Used in post listings and SEO.',
                ],
                'featured_image' => [
                    'type' => 'string',
                    'description' => 'URL or storage path for the featured image.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['published', 'draft'],
                    'description' => 'The post status. Default is "published".',
                ],
                'seo_title' => [
                    'type' => 'string',
                    'description' => 'Custom SEO title. Defaults to the post title.',
                ],
                'seo_description' => [
                    'type' => 'string',
                    'description' => 'SEO meta description (recommended 150-160 characters).',
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
            $post = Post::create([
                'title' => $title,
                'content' => $params['content'],
                'excerpt' => $params['excerpt'] ?? Str::limit(strip_tags($params['content']), 160),
                'featured_image' => $params['featured_image'] ?? null,
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
                'author_id' => auth()->id() ?? 1,
                'seo_title' => $params['seo_title'] ?? null,
                'seo_description' => $params['seo_description'] ?? $params['excerpt'] ?? null,
            ]);

            return $this->success([
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'url' => '/blog/' . $post->slug,
                'status' => $post->status,
            ], "Blog post '{$title}' created successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to create post: {$e->getMessage()}");
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

        // Find by title since slug may have been auto-generated
        $post = Post::where('title', $rollbackData['title'])->latest()->first();
        if ($post) {
            $post->delete();
            return true;
        }
        return false;
    }
}
