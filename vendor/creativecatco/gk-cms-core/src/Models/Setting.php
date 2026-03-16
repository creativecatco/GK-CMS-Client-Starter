<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Cache key prefix for settings.
     */
    protected static string $cachePrefix = 'cms_setting_';

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(static::$cachePrefix . $key, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget(static::$cachePrefix . $key);
    }

    /**
     * Get all settings in a group.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Flush all cached settings.
     */
    public static function flushCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(static::$cachePrefix . $key);
        }
    }

    /**
     * Scope a query to filter by group.
     */
    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
