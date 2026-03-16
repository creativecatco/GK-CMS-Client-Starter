<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'seo_title',
        'seo_description',
        'og_image',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'author_id');
    }

    /**
     * Get the categories for the post.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'post_category');
    }

    /**
     * Get the tags for the post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft posts.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to order by published date (newest first).
     */
    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->orderByDesc('published_at');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->whereHas('categories', function (Builder $q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * Scope a query to filter by tag.
     */
    public function scopeWithTag(Builder $query, string $tagSlug): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    /**
     * Get the effective SEO title.
     */
    public function getSeoTitleAttribute(?string $value): string
    {
        return $value ?: $this->title;
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

    /**
     * Get the effective OG image URL.
     */
    public function getOgImageUrlAttribute(): ?string
    {
        $image = $this->og_image ?: $this->featured_image;
        if (!$image) {
            return null;
        }
        if (str_starts_with($image, 'http')) {
            return $image;
        }
        return asset('storage/' . $image);
    }
}
