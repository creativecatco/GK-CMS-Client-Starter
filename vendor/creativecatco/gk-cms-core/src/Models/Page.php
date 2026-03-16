<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'page_type',
        'template',
        'content',
        'blocks',
        'fields',
        'field_definitions',
        'custom_template',
        'custom_css',
        'featured_image',
        'status',
        'sort_order',
        'seo_title',
        'seo_description',
        'og_image',
        'show_in_nav',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'blocks' => 'array',
        'fields' => 'array',
        'field_definitions' => 'array',
        'show_in_nav' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    |
    | Supported field types for editable regions.
    | Each type maps to a Filament form component in the admin panel
    | and a specific inline editor behavior on the frontend.
    |
    */

    public const FIELD_TYPES = [
        'text'         => 'Single line text',
        'textarea'     => 'Multi-line text',
        'richtext'     => 'Rich text editor',
        'image'        => 'Image upload',
        'url'          => 'URL / Link',
        'color'        => 'Color picker',
        'select'       => 'Dropdown select',
        'number'       => 'Number',
        'toggle'       => 'Toggle (on/off)',
        'button'       => 'Button (text + link + style)',
        'button_group' => 'Button Group (multiple buttons)',
        'section_bg'   => 'Section Background (color/image/mode)',
        'gallery'      => 'Image Gallery (grid/slider)',
        'video'        => 'Video / Embed (YouTube, Vimeo, upload)',
        'icon'         => 'Icon picker (SVG icons)',
        'repeater'     => 'Repeater (list of items with sub-fields)',
    ];

    /*
    |--------------------------------------------------------------------------
    | Field Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this page has editable fields defined.
     */
    public function hasFields(): bool
    {
        return !empty($this->field_definitions) && is_array($this->field_definitions);
    }

    /**
     * Get a specific field value with optional default.
     */
    public function field(string $key, $default = null)
    {
        $fields = $this->fields ?? [];
        return $fields[$key] ?? $default;
    }

    /**
     * Get a button field value as a structured array.
     * Returns: ['text' => '...', 'link' => '...', 'style' => '...']
     */
    public function button(string $key, array $defaults = []): array
    {
        $value = $this->field($key);
        if (is_array($value)) {
            return array_merge([
                'text' => 'Click Here',
                'link' => '#',
                'style' => 'primary',
                'visible' => true,
            ], $defaults, $value);
        }
        return array_merge([
            'text' => $value ?? 'Click Here',
            'link' => '#',
            'style' => 'primary',
            'visible' => true,
        ], $defaults);
    }

    /**
     * Render a button as HTML. Safe to use with {!! !!} in Blade templates.
     * Uses the theme's primary/secondary colors for styling.
     */
    public function renderButton(string $key, array $defaults = []): string
    {
        $btn = $this->button($key, $defaults);
        if (!($btn['visible'] ?? true)) {
            return '';
        }

        $text = e($btn['text'] ?? 'Click Here');
        $link = e($btn['link'] ?? '#');
        $style = $btn['style'] ?? 'primary';

        if ($style === 'primary') {
            $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90 hover:shadow-lg';
            $inlineStyle = 'background-color: var(--color-primary); color: var(--color-secondary);';
        } elseif ($style === 'secondary') {
            $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90 border-2';
            $inlineStyle = 'border-color: var(--color-primary); color: var(--color-primary);';
        } else {
            $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90';
            $inlineStyle = '';
        }

        return '<a href="' . $link . '" class="' . $classes . '" style="' . $inlineStyle . '" data-field="' . e($key) . '" data-field-type="button">' . $text . '</a>';
    }

    /**
     * Get a button group field value as an array of button arrays.
     */
    public function buttonGroup(string $key, array $defaults = []): array
    {
        $value = $this->field($key, []);
        if (!is_array($value)) return [];
        return array_map(function ($btn) use ($defaults) {
            return array_merge([
                'text' => 'Click Here',
                'link' => '#',
                'style' => 'primary',
                'visible' => true,
            ], $defaults, is_array($btn) ? $btn : ['text' => $btn]);
        }, $value);
    }

    /**
     * Get a section background field value as a structured array.
     * Returns: ['color' => '...', 'image' => '...', 'mode' => 'static|parallax|repeat|cover']
     */
    public function sectionBg(string $key, array $defaults = []): array
    {
        $value = $this->field($key);
        if (is_array($value)) {
            return array_merge([
                'color' => '',
                'image' => '',
                'mode' => 'cover',
                'overlay' => '',
                'overlay_opacity' => '0.5',
            ], $defaults, $value);
        }
        return array_merge([
            'color' => $value ?? '',
            'image' => '',
            'mode' => 'cover',
            'overlay' => '',
            'overlay_opacity' => '0.5',
        ], $defaults);
    }

    /**
     * Generate inline CSS for a section background.
     */
    public function sectionBgStyle(string $key, array $defaults = []): string
    {
        $bg = $this->sectionBg($key, $defaults);
        $styles = [];

        if (!empty($bg['color'])) {
            $styles[] = "background-color: {$bg['color']}";
        }
        if (!empty($bg['image'])) {
            $url = asset('storage/' . $bg['image']);
            $styles[] = "background-image: url('{$url}')";

            switch ($bg['mode'] ?? 'cover') {
                case 'parallax':
                    $styles[] = 'background-attachment: fixed';
                    $styles[] = 'background-size: cover';
                    $styles[] = 'background-position: center';
                    break;
                case 'repeat':
                    $styles[] = 'background-repeat: repeat';
                    $styles[] = 'background-size: auto';
                    break;
                case 'static':
                    $styles[] = 'background-attachment: scroll';
                    $styles[] = 'background-size: cover';
                    $styles[] = 'background-position: center';
                    break;
                case 'cover':
                default:
                    $styles[] = 'background-size: cover';
                    $styles[] = 'background-position: center';
                    break;
            }
        }

        return implode('; ', $styles);
    }

    /**
     * Get all field values as an object for easy Blade access.
     */
    public function getFieldsObjectAttribute(): object
    {
        return (object) ($this->fields ?? []);
    }

    /**
     * Get field definitions grouped by their 'group' key.
     */
    public function getGroupedFieldDefinitions(): array
    {
        if (!$this->hasFields()) {
            return [];
        }

        $grouped = [];
        foreach ($this->field_definitions as $definition) {
            $group = $definition['group'] ?? 'General';
            $grouped[$group][] = $definition;
        }

        // Sort fields within each group by order
        foreach ($grouped as &$fields) {
            usort($fields, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        }

        return $grouped;
    }

    /**
     * Auto-discover field definitions from a Blade template string.
     */
    public static function discoverFieldsFromTemplate(string $templateContent): array
    {
        $fields = [];
        $seen = [];

        // Pattern 1: data-field attributes (preferred, explicit)
        preg_match_all(
            '/data-field=["\']([^"\']+)["\']/s',
            $templateContent,
            $dataFieldMatches
        );

        foreach ($dataFieldMatches[1] as $key) {
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $type = 'text';
            $label = self::keyToLabel($key);
            $group = 'General';
            $placeholder = '';

            if (preg_match('/data-field=["\']' . preg_quote($key, '/') . '["\'][^>]*data-field-type=["\']([^"\']+)["\']/s', $templateContent, $m)) {
                $type = $m[1];
            }
            if (preg_match('/data-field=["\']' . preg_quote($key, '/') . '["\'][^>]*data-field-label=["\']([^"\']+)["\']/s', $templateContent, $m)) {
                $label = $m[1];
            }
            if (preg_match('/data-field=["\']' . preg_quote($key, '/') . '["\'][^>]*data-field-group=["\']([^"\']+)["\']/s', $templateContent, $m)) {
                $group = $m[1];
            }

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'group' => $group,
                'placeholder' => $placeholder,
                'order' => count($fields),
            ];
        }

        // Pattern 2: {{ $fields['key'] }} or {!! $fields['key'] !!}
        preg_match_all(
            '/(?:\{\{|\{!!)\s*\$fields\[[\'"]([^\'"]+)[\'"]\]\s*(?:\?\?[^}]*)?\s*(?:\}\}|!!\})|' .
            '\$page->field\([\'"]([^\'"]+)[\'"]/s',
            $templateContent,
            $varMatches
        );

        foreach ($varMatches[1] as $i => $key) {
            $key = $key ?: ($varMatches[2][$i] ?? '');
            if (empty($key) || isset($seen[$key])) continue;
            $seen[$key] = true;

            $type = self::inferFieldType($key);
            $group = self::inferFieldGroup($key);

            $fields[] = [
                'key' => $key,
                'label' => self::keyToLabel($key),
                'type' => $type,
                'group' => $group,
                'placeholder' => '',
                'order' => count($fields),
            ];
        }

        return $fields;
    }

    /**
     * Convert a snake_case key to a human-readable label.
     */
    public static function keyToLabel(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Infer the field type from the key name.
     */
    protected static function inferFieldType(string $key): string
    {
        $key = strtolower($key);

        if (str_contains($key, 'image') || str_contains($key, 'photo') || str_contains($key, 'logo') || str_contains($key, 'avatar')) {
            return 'image';
        }
        if (str_contains($key, '_bg') || str_contains($key, 'background')) {
            return 'section_bg';
        }
        if (str_contains($key, 'button') && str_contains($key, 'group')) {
            return 'button_group';
        }
        if (str_contains($key, 'button') || str_contains($key, '_btn') || str_contains($key, '_cta')) {
            return 'button';
        }
        if (str_contains($key, 'url') || str_contains($key, 'link') || str_contains($key, 'href')) {
            return 'url';
        }
        if (str_contains($key, 'color') || str_contains($key, 'colour')) {
            return 'color';
        }
        if (str_contains($key, 'gallery') && str_contains($key, 'image')) {
            return 'gallery';
        }
        if (str_contains($key, 'video') || str_contains($key, 'embed')) {
            return 'video';
        }
        if (str_contains($key, 'icon')) {
            return 'icon';
        }
        if (str_contains($key, '_items') || str_contains($key, '_list') || str_contains($key, '_repeater')) {
            return 'repeater';
        }
        if (str_contains($key, 'description') || str_contains($key, 'bio') || str_contains($key, 'body') || str_contains($key, 'content') || str_contains($key, 'paragraph')) {
            return 'textarea';
        }
        if (str_contains($key, 'html') || str_contains($key, 'rich')) {
            return 'richtext';
        }
        if (str_contains($key, 'count') || str_contains($key, 'number') || str_contains($key, 'amount') || str_contains($key, 'price') || str_contains($key, 'quantity')) {
            return 'number';
        }
        if (str_contains($key, 'enabled') || str_contains($key, 'visible') || str_contains($key, 'show') || str_contains($key, 'active')) {
            return 'toggle';
        }

        return 'text';
    }

    /**
     * Infer the field group from the key name.
     */
    protected static function inferFieldGroup(string $key): string
    {
        $key = strtolower($key);
        $parts = explode('_', $key);
        if (count($parts) >= 2) {
            $prefix = $parts[0];
            $commonPrefixes = ['hero', 'about', 'cta', 'footer', 'header', 'nav', 'contact', 'team', 'services', 'features', 'pricing', 'faq', 'testimonial', 'gallery', 'blog', 'banner', 'section', 'video', 'accent', 'stats'];
            if (in_array($prefix, $commonPrefixes)) {
                return ucfirst($prefix);
            }
        }
        return 'General';
    }

    /**
     * Check if this page uses the block builder (legacy support).
     */
    public function hasBlocks(): bool
    {
        return !empty($this->blocks) && is_array($this->blocks);
    }

    /**
     * Get blocks of a specific type (legacy support).
     */
    public function getBlocksByType(string $type): array
    {
        if (!$this->hasBlocks()) {
            return [];
        }
        return collect($this->blocks)
            ->filter(fn ($block) => ($block['type'] ?? '') === $type)
            ->values()
            ->toArray();
    }

    /**
     * Get available page templates by scanning the theme and package view directories.
     */
    public static function getAvailableTemplates(): array
    {
        $templates = ['custom' => 'Custom (AI-Generated)'];

        // Internal/utility templates that should never appear in the dropdown
        $excluded = [
            'default', 'custom', 'custom-render',
            // Internal component templates (header, footer, single post/portfolio/product)
            'default-header', 'default-footer',
            'default-post', 'default-portfolio-archive', 'default-portfolio-single',
            'default-product-single', 'default-products-archive',
            // Unused/demo templates
            'example-custom', 'full-width', 'landing',
            // Old duplicates without "default-" prefix
            'about', 'contact',
        ];

        $themePath = resource_path('views/theme/pages');
        if (is_dir($themePath)) {
            foreach (glob($themePath . '/*.blade.php') as $file) {
                $name = basename($file, '.blade.php');
                if (!in_array($name, $excluded) && !isset($templates[$name])) {
                    $templates[$name] = str_replace(['-', '_'], ' ', ucwords($name, '-_'));
                }
            }
        }

        $packagePath = dirname(__DIR__, 2) . '/resources/views/pages';
        if (is_dir($packagePath)) {
            foreach (glob($packagePath . '/*.blade.php') as $file) {
                $name = basename($file, '.blade.php');
                if (!in_array($name, $excluded) && !isset($templates[$name])) {
                    $templates[$name] = str_replace(['-', '_'], ' ', ucwords($name, '-_'));
                }
            }
        }

        return $templates;
    }

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
     * Scope: published pages (excludes header/footer).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(function ($q) {
            $q->whereIn('page_type', ['page', 'post'])->orWhereNull('page_type');
        });
    }

    /**
     * Scope: header component.
     */
    public function scopeHeader(Builder $query): Builder
    {
        return $query->where('page_type', 'header');
    }

    /**
     * Scope: footer component.
     */
    public function scopeFooter(Builder $query): Builder
    {
        return $query->where('page_type', 'footer');
    }

    /**
     * Get the active header page.
     */
    public static function getHeader(): ?self
    {
        return static::header()->where('status', 'published')->first();
    }

    /**
     * Get the active footer page.
     */
    public static function getFooter(): ?self
    {
        return static::footer()->where('status', 'published')->first();
    }

    /**
     * Scope: draft pages.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: ordered by sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
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
