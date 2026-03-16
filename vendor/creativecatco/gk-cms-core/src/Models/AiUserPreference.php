<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserPreference extends Model
{
    protected $table = 'ai_user_preferences';

    protected $fillable = [
        'user_id',
        'category',
        'key',
        'value',
    ];

    /**
     * Valid categories for preferences.
     */
    public const CATEGORIES = [
        'brand',      // Brand voice, tone, company info
        'design',     // Color preferences, style preferences, layout preferences
        'content',    // Writing style, terminology, content guidelines
        'technical',  // Preferred frameworks, coding style, etc.
        'general',    // Miscellaneous preferences
    ];

    /**
     * Get the user that owns this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get all preferences for a user, grouped by category.
     */
    public static function getAllForUser(int $userId): array
    {
        $prefs = static::where('user_id', $userId)
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $grouped = [];
        foreach ($prefs as $pref) {
            $grouped[$pref->category][$pref->key] = $pref->value;
        }

        return $grouped;
    }

    /**
     * Get preferences for a user in a specific category.
     */
    public static function getCategory(int $userId, string $category): array
    {
        return static::where('user_id', $userId)
            ->where('category', $category)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Set a preference (create or update).
     */
    public static function setPreference(int $userId, string $category, string $key, string $value): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'category' => $category,
                'key' => $key,
            ],
            ['value' => $value]
        );
    }

    /**
     * Delete a specific preference.
     */
    public static function deletePreference(int $userId, string $category, string $key): bool
    {
        return static::where('user_id', $userId)
            ->where('category', $category)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Format all preferences as a context string for the AI.
     */
    public static function getContextString(int $userId): string
    {
        $prefs = static::getAllForUser($userId);

        if (empty($prefs)) {
            return '';
        }

        $lines = ["[USER PREFERENCES — Remember these across all conversations]\n"];

        $categoryLabels = [
            'brand' => 'Brand & Voice',
            'design' => 'Design Preferences',
            'content' => 'Content Guidelines',
            'technical' => 'Technical Preferences',
            'general' => 'General Notes',
        ];

        foreach ($prefs as $category => $items) {
            $label = $categoryLabels[$category] ?? ucfirst($category);
            $lines[] = "### {$label}";
            foreach ($items as $key => $value) {
                $lines[] = "- **{$key}**: {$value}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
