<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Portfolio;
use Illuminate\Support\Str;

class CreatePortfolioTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_portfolio';
    }

    public function description(): string
    {
        return 'Create a new portfolio item. Portfolio items showcase completed projects/work and are displayed on the portfolio page.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The portfolio item title (project name).',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Detailed description of the project. Supports HTML.',
                ],
                'excerpt' => [
                    'type' => 'string',
                    'description' => 'A short summary for listings.',
                ],
                'client' => [
                    'type' => 'string',
                    'description' => 'The client name for this project.',
                ],
                'project_url' => [
                    'type' => 'string',
                    'description' => 'URL to the live project (if applicable).',
                ],
                'featured_image' => [
                    'type' => 'string',
                    'description' => 'URL or storage path for the featured image.',
                ],
                'gallery' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Array of image URLs or storage paths for the project gallery.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['published', 'draft'],
                    'description' => 'The portfolio item status. Default is "published".',
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
            $portfolio = Portfolio::create([
                'title' => $title,
                'content' => $params['content'],
                'excerpt' => $params['excerpt'] ?? Str::limit(strip_tags($params['content']), 160),
                'client' => $params['client'] ?? null,
                'project_url' => $params['project_url'] ?? null,
                'featured_image' => $params['featured_image'] ?? null,
                'gallery' => $params['gallery'] ?? [],
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
                'author_id' => auth()->id() ?? 1,
            ]);

            return $this->success([
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'slug' => $portfolio->slug,
                'url' => '/portfolio/' . $portfolio->slug,
                'status' => $portfolio->status,
            ], "Portfolio item '{$title}' created successfully.");
        } catch (\Exception $e) {
            return $this->error("Failed to create portfolio item: {$e->getMessage()}");
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

        $portfolio = Portfolio::where('title', $rollbackData['title'])->latest()->first();
        if ($portfolio) {
            $portfolio->delete();
            return true;
        }
        return false;
    }
}
