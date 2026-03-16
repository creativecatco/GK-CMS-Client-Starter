<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
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
     * Get the posts for the category.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_category');
    }

    /**
     * Get the portfolios for the category.
     */
    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'portfolio_category');
    }

    /**
     * Get the products for the category.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    /**
     * Get the count of published posts in this category.
     */
    public function publishedPostsCount(): int
    {
        return $this->posts()->published()->count();
    }
}
