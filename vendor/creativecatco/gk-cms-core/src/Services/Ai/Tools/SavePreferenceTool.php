<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\AiUserPreference;

class SavePreferenceTool extends AbstractTool
{
    public function name(): string
    {
        return 'save_preference';
    }

    public function description(): string
    {
        return 'Save a user preference that should be remembered across all conversations. Use this to store brand voice, design preferences, content guidelines, or any other preference the user mentions. Categories: brand (company info, tone, voice), design (colors, styles, layouts), content (writing style, terminology), technical (frameworks, coding style), general (misc notes).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => AiUserPreference::CATEGORIES,
                    'description' => 'The preference category',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'A short descriptive key for the preference (e.g., "brand_voice", "primary_color", "writing_tone")',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'The preference value to remember',
                ],
            ],
            'required' => ['category', 'key', 'value'],
        ];
    }

    public function execute(array $params): array
    {
        $category = $params['category'] ?? 'general';
        $key = $params['key'] ?? '';
        $value = $params['value'] ?? '';

        if (empty($key) || empty($value)) {
            return $this->error('Both key and value are required');
        }

        if (!in_array($category, AiUserPreference::CATEGORIES)) {
            return $this->error('Invalid category. Valid: ' . implode(', ', AiUserPreference::CATEGORIES));
        }

        $userId = auth()->id();
        if (!$userId) {
            return $this->error('No user context available');
        }

        try {
            AiUserPreference::setPreference($userId, $category, $key, $value);

            return $this->success(
                ['category' => $category, 'key' => $key, 'value' => $value],
                "Preference saved: [{$category}] {$key} = {$value}"
            );
        } catch (\Exception $e) {
            return $this->error('Failed to save preference: ' . $e->getMessage());
        }
    }
}
