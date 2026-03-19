<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Symfony\Component\DomCrawler\Crawler;

class Page extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'page_type',
        'display_scope',
        'display_on',
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
        'display_on' => 'array',
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
     * Check if this page has block-based content (legacy).
     */
    public function hasBlocks(): bool
    {
        return !empty($this->blocks) && is_array($this->blocks);
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
     *
     * IMPORTANT: This now returns a ButtonResult (ArrayObject) that renders as HTML
     * when echoed via {{ }} or {!! !!}}, preventing 500 errors from AI-generated templates.
     */
    public function button(string $key, array $defaults = []): \ArrayObject
    {
        $value = $this->field($key);
        if (is_array($value)) {
            $data = array_merge([
                'text' => 'Click Here',
                'link' => '#',
                'style' => 'primary',
                'visible' => true,
            ], $defaults, $value);
        } else {
            $data = array_merge([
                'text' => $value ?? 'Click Here',
                'link' => '#',
                'style' => 'primary',
                'visible' => true,
            ], $defaults);
        }

        // Return a ButtonResult that works as both an array AND a string
        return new class($data, $key) extends \ArrayObject implements \Stringable {
            private string $fieldKey;

            public function __construct(array $data, string $key)
            {
                parent::__construct($data, \ArrayObject::ARRAY_AS_PROPS);
                $this->fieldKey = $key;
            }

            public function __toString(): string
            {
                $btn = $this->getArrayCopy();
                if (!($btn['visible'] ?? true)) {
                    return '';
                }
                $text = e($btn['text'] ?? 'Click Here');
                $link = e($btn['link'] ?? '#');
                $style = $btn['style'] ?? 'primary';
                $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90 hover:shadow-lg';
                if ($style === 'primary') {
                    $inlineStyle = 'background-color: var(--color-primary); color: var(--color-secondary);';
                } elseif ($style === 'secondary') {
                    $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90 border-2';
                    $inlineStyle = 'border-color: var(--color-primary); color: var(--color-primary);';
                } else {
                    $inlineStyle = '';
                }
                return '<a href="' . $link . '" class="' . $classes . '" style="' . $inlineStyle . '" data-field="' . e($this->fieldKey) . '" data-field-type="button">' . $text . '</a>';
            }
        };
    }

    /**
     * Render a button as HTML. Safe to use with {!! !!} in Blade templates.
     * Uses the theme's primary/secondary colors for styling.
     * Supports optional custom_color and custom_text_color overrides.
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
        $customColor = $btn['custom_color'] ?? null;
        $customTextColor = $btn['custom_text_color'] ?? null;

        $classes = 'inline-block px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 hover:opacity-90 hover:shadow-lg';
        $inlineStyle = '';

        if (!empty($customColor)) {
            // Custom color override — takes priority over style presets
            $inlineStyle = 'background-color: ' . e($customColor) . ';';
            if (!empty($customTextColor)) {
                $inlineStyle .= ' color: ' . e($customTextColor) . ';';
            } else {
                // Auto-detect contrast: light bg gets dark text, dark bg gets white text
                $hex = ltrim($customColor, '#');
                if (strlen($hex) === 6) {
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                    $inlineStyle .= ' color: ' . ($yiq >= 128 ? '#000000' : '#FFFFFF') . ';';
                } else {
                    $inlineStyle .= ' color: #FFFFFF;';
                }
            }
        } elseif ($style === 'primary') {
            $inlineStyle = 'background-color: var(--color-primary); color: var(--color-secondary);';
        } elseif ($style === 'secondary') {
            $classes .= ' border-2';
            $inlineStyle = 'border-color: var(--color-primary); color: var(--color-primary); background-color: transparent;';
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

        $crawler = new Crawler($templateContent);

        // Pattern 1: data-field attributes (preferred, explicit)
        $crawler->filter('[data-field]')->each(function (Crawler $node) use (&$fields, &$seen) {
            $key = $node->attr('data-field');
            if (empty($key) || isset($seen[$key])) return;
            $seen[$key] = true;

            $type = $node->attr('data-field-type') ?: 'text';
            $label = $node->attr('data-field-label') ?: self::keyToLabel($key);
            $group = $node->attr('data-field-group') ?: 'General';
            $placeholder = $node->attr('data-field-placeholder') ?: '';

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'group' => $group,
                'placeholder' => $placeholder,
                'order' => count($fields),
            ];
        });

        // Pattern 2: Blade variables (fallback)
        // This pattern needs to be adjusted to work with the new DOM parsing approach
        // For now, we'll keep the original regex for Blade variables as a fallback
        // but ideally, this should also be handled by parsing the Blade syntax more robustly.
        preg_match_all('/{{(?:\s*\$page->field\([\'"]([^\'"]+)[\'"](?:,\s*[^)]+)?\)\s*|\s*\$page->fieldsObject->([^\s]+)\s*)}}/', $templateContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1] ?? $match[2];
            if (empty($key) || isset($seen[$key])) continue;
            $seen[$key] = true;

            $fields[] = [
                'key' => $key,
                'label' => self::keyToLabel($key),
                'type' => 'text', // Default to text for Blade variables
                'group' => 'General',
                'placeholder' => '',
                'order' => count($fields),
            ];
        }

        return $fields;
    }

    /**
     * Convert a field key to a human-readable label.
     */
    protected static function keyToLabel(string $key): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $key));
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include pages with a specific page type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('page_type', $type);
    }

    /**
     * Get available page templates by scanning view directories.
     * Returns an associative array of [template_key => Label] for use in Filament select fields.
     */
    public static function getAvailableTemplates(): array
    {
        $templates = [
            'custom' => 'Custom (AI-Generated)',
        ];

        // Scan the CMS core package views for page templates
        $paths = [
            resource_path('views/theme/pages'),
            resource_path('views/vendor/cms-core/pages'),
        ];

        // Also check the package's own views directory
        $packagePath = dirname(__DIR__, 2) . '/resources/views/pages';
        if (is_dir($packagePath)) {
            $paths[] = $packagePath;
        }

        $seen = ['custom' => true, 'custom-render' => true]; // Skip these

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (glob($path . '/*.blade.php') as $file) {
                $name = basename($file, '.blade.php');

                // Skip already seen templates
                if (isset($seen[$name])) {
                    continue;
                }
                $seen[$name] = true;

                // Convert filename to a readable label
                $label = ucwords(str_replace(['-', '_'], ' ', $name));
                $templates[$name] = $label;
            }
        }

        // Sort alphabetically but keep 'custom' first
        $custom = ['custom' => $templates['custom']];
        unset($templates['custom']);
        asort($templates);
        $templates = $custom + $templates;

        return $templates;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
