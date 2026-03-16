<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Portfolio extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'gallery',
        'client',
        'project_url',
        'project_date',
        'status',
        'published_at',
        'author_id',
        'seo_title',
        'seo_description',
        'og_image',
        'sort_order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'project_date' => 'date',
        'gallery' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'portfolio_category');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->orderByDesc('published_at')->orderByDesc('created_at');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    /**
     * Get the featured image URL with storage path handling.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) {
            return null;
        }

        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }

        return asset('storage/' . $this->featured_image);
    }
}
