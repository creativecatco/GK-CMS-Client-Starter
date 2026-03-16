<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    protected $fillable = [
        'name',
        'location',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    /**
     * Get a menu by its location (e.g., 'header', 'footer').
     * Returns the items array or empty array if not found.
     */
    public static function getByLocation(string $location): array
    {
        return Cache::remember('menu_' . $location, 3600, function () use ($location) {
            $menu = static::where('location', $location)->first();
            return $menu ? ($menu->items ?? []) : [];
        });
    }

    /**
     * Flush cached menus.
     */
    public static function flushCache(): void
    {
        Cache::forget('menu_header');
        Cache::forget('menu_footer');
        Cache::forget('menu_footer_secondary');
    }

    /**
     * Boot the model and clear cache on save/delete.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::flushCache();
        });

        static::deleted(function () {
            static::flushCache();
        });
    }
}
