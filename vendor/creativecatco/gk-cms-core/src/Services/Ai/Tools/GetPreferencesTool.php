<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\AiUserPreference;

class GetPreferencesTool extends AbstractTool
{
    public function name(): string
    {
        return 'get_preferences';
    }

    public function description(): string
    {
        return 'Retrieve saved user preferences. Can get all preferences or filter by category. Use this at the start of conversations to recall the user\'s brand voice, design preferences, content guidelines, etc.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => array_merge(AiUserPreference::CATEGORIES, ['all']),
                    'description' => 'The preference category to retrieve, or "all" for everything. Default: "all"',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        $category = $params['category'] ?? 'all';

        $userId = auth()->id();
        if (!$userId) {
            return $this->error('No user context available');
        }

        try {
            if ($category === 'all') {
                $preferences = AiUserPreference::getAllForUser($userId);
            } else {
                $preferences = [$category => AiUserPreference::getCategory($userId, $category)];
            }

            // Remove empty categories
            $preferences = array_filter($preferences);

            if (empty($preferences)) {
                return $this->success(
                    ['preferences' => []],
                    'No preferences saved yet. Use save_preference to store user preferences for future conversations.'
                );
            }

            return $this->success(
                ['preferences' => $preferences],
                'Preferences retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to get preferences: ' . $e->getMessage());
        }
    }
}
