<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use CreativeCatCo\GkCmsCore\Models\Page as CmsPage;
use CreativeCatCo\GkCmsCore\Models\Setting;
use CreativeCatCo\GkCmsCore\Models\Menu;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class SearchReplacePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Search & Replace';

    protected static ?string $slug = 'search-replace';

    protected static string $view = 'cms-core::filament.pages.search-replace';

    public ?string $search_term = '';
    public ?string $replace_term = '';
    public ?array $scan_results = null;
    public bool $has_scanned = false;

    public function scan(): void
    {
        if (empty($this->search_term)) {
            Notification::make()
                ->title('Please enter a search term')
                ->warning()
                ->send();
            return;
        }

        $results = [];
        $term = $this->search_term;

        // Search in Pages (all types including header, footer, etc.)
        if (Schema::hasTable('pages')) {
            try {
                // Use the base query to get ALL pages, not filtered by PageResource scope
                $pages = CmsPage::withoutGlobalScopes()->get();
                foreach ($pages as $page) {
                    $matches = [];

                    if (str_contains($page->title ?? '', $term)) {
                        $matches[] = 'Title';
                    }
                    if (str_contains($page->slug ?? '', $term)) {
                        $matches[] = 'Slug';
                    }
                    if (str_contains($page->content ?? '', $term)) {
                        $matches[] = 'Content';
                    }
                    if (str_contains($page->custom_template ?? '', $term)) {
                        $matches[] = 'Custom Template';
                    }
                    if (str_contains($page->custom_css ?? '', $term)) {
                        $matches[] = 'Page CSS';
                    }
                    if (str_contains($page->seo_title ?? '', $term)) {
                        $matches[] = 'SEO Title';
                    }
                    if (str_contains($page->seo_description ?? '', $term)) {
                        $matches[] = 'SEO Description';
                    }

                    // Deep search in fields JSON (handles nested arrays/repeaters)
                    $fieldsJson = json_encode($page->fields ?? []);
                    if (str_contains($fieldsJson, $term)) {
                        $matches[] = 'Fields (editable content)';
                    }

                    // Search in field_definitions JSON
                    $defsJson = json_encode($page->field_definitions ?? []);
                    if (str_contains($defsJson, $term)) {
                        $matches[] = 'Field Definitions';
                    }

                    if (!empty($matches)) {
                        $typeLabel = match ($page->page_type) {
                            'post' => 'Blog Post',
                            'header' => 'Header',
                            'footer' => 'Footer',
                            'archive' => 'Archive Template',
                            default => 'Page',
                        };
                        $results[] = [
                            'type' => $typeLabel,
                            'name' => $page->title,
                            'id' => $page->id,
                            'locations' => implode(', ', $matches),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Log but don't break
                \Log::warning('Search & Replace: Error scanning pages: ' . $e->getMessage());
            }
        }

        // Search in Posts table (if it exists separately)
        if (Schema::hasTable('posts')) {
            try {
                $posts = \CreativeCatCo\GkCmsCore\Models\Post::all();
                foreach ($posts as $post) {
                    $matches = [];

                    if (str_contains($post->title ?? '', $term)) {
                        $matches[] = 'Title';
                    }
                    if (str_contains($post->content ?? '', $term)) {
                        $matches[] = 'Content';
                    }
                    if (str_contains($post->excerpt ?? '', $term)) {
                        $matches[] = 'Excerpt';
                    }

                    if (!empty($matches)) {
                        $results[] = [
                            'type' => 'Post (legacy)',
                            'name' => $post->title,
                            'id' => $post->id,
                            'locations' => implode(', ', $matches),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Posts table may not exist, skip silently
            }
        }

        // Search in Settings
        if (Schema::hasTable('settings')) {
            try {
                $settings = Setting::all();
                foreach ($settings as $setting) {
                    if (str_contains($setting->value ?? '', $term)) {
                        $results[] = [
                            'type' => 'Setting',
                            'name' => $setting->key . ' (' . ($setting->group ?? 'general') . ')',
                            'id' => $setting->id,
                            'locations' => 'Value: ' . \Illuminate\Support\Str::limit($setting->value, 80),
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Search & Replace: Error scanning settings: ' . $e->getMessage());
            }
        }

        // Search in Menus
        if (Schema::hasTable('menus')) {
            try {
                $menus = Menu::all();
                foreach ($menus as $menu) {
                    $menuJson = json_encode($menu->items ?? []);
                    if (str_contains($menuJson, $term)) {
                        $results[] = [
                            'type' => 'Menu',
                            'name' => $menu->name,
                            'id' => $menu->id,
                            'locations' => 'Menu Items (URLs/labels)',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Menus table may not exist
            }
        }

        // Search in theme template files on disk
        $templateDirs = [
            resource_path('views/theme') => 'Theme Template',
            resource_path('views/vendor/cms-core') => 'Published View',
        ];
        foreach ($templateDirs as $dir => $typeLabel) {
            if (is_dir($dir)) {
                $files = File::allFiles($dir);
                foreach ($files as $file) {
                    $content = $file->getContents();
                    if (str_contains($content, $term)) {
                        $results[] = [
                            'type' => $typeLabel,
                            'name' => $file->getRelativePathname(),
                            'id' => 0,
                            'locations' => 'File content (on disk)',
                        ];
                    }
                }
            }
        }

        $this->scan_results = $results;
        $this->has_scanned = true;

        $count = count($results);
        Notification::make()
            ->title("Found {$count} item(s) containing \"{$term}\"")
            ->info()
            ->send();
    }

    public function executeReplace(): void
    {
        if (empty($this->search_term)) {
            Notification::make()
                ->title('Please enter a search term')
                ->warning()
                ->send();
            return;
        }

        $term = $this->search_term;
        $replacement = $this->replace_term ?? '';
        $totalReplacements = 0;

        // Replace in Pages (all types)
        if (Schema::hasTable('pages')) {
            try {
                $pages = CmsPage::withoutGlobalScopes()->get();
                foreach ($pages as $page) {
                    $changed = false;

                    foreach (['title', 'content', 'custom_template', 'custom_css', 'seo_title', 'seo_description'] as $col) {
                        if (str_contains($page->{$col} ?? '', $term)) {
                            $page->{$col} = str_replace($term, $replacement, $page->{$col});
                            $changed = true;
                        }
                    }

                    // Replace in slug
                    if (str_contains($page->slug ?? '', $term)) {
                        $page->slug = str_replace($term, $replacement, $page->slug);
                        $changed = true;
                    }

                    // Replace in fields JSON (deep)
                    $fieldsJson = json_encode($page->fields ?? []);
                    if (str_contains($fieldsJson, $term)) {
                        $fieldsJson = str_replace($term, $replacement, $fieldsJson);
                        $page->fields = json_decode($fieldsJson, true);
                        $changed = true;
                    }

                    // Replace in field_definitions JSON
                    $defsJson = json_encode($page->field_definitions ?? []);
                    if (str_contains($defsJson, $term)) {
                        $defsJson = str_replace($term, $replacement, $defsJson);
                        $page->field_definitions = json_decode($defsJson, true);
                        $changed = true;
                    }

                    if ($changed) {
                        $page->save();
                        $totalReplacements++;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Search & Replace: Error replacing in pages: ' . $e->getMessage());
            }
        }

        // Replace in Posts (legacy table)
        if (Schema::hasTable('posts')) {
            try {
                $posts = \CreativeCatCo\GkCmsCore\Models\Post::all();
                foreach ($posts as $post) {
                    $changed = false;

                    foreach (['title', 'content', 'excerpt'] as $col) {
                        if (str_contains($post->{$col} ?? '', $term)) {
                            $post->{$col} = str_replace($term, $replacement, $post->{$col});
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $post->save();
                        $totalReplacements++;
                    }
                }
            } catch (\Exception $e) {
                // Skip silently
            }
        }

        // Replace in Settings
        if (Schema::hasTable('settings')) {
            try {
                $settings = Setting::all();
                foreach ($settings as $setting) {
                    if (str_contains($setting->value ?? '', $term)) {
                        $setting->value = str_replace($term, $replacement, $setting->value);
                        $setting->save();
                        $totalReplacements++;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Search & Replace: Error replacing in settings: ' . $e->getMessage());
            }
        }

        // Replace in Menus
        if (Schema::hasTable('menus')) {
            try {
                $menus = Menu::all();
                foreach ($menus as $menu) {
                    $menuJson = json_encode($menu->items ?? []);
                    if (str_contains($menuJson, $term)) {
                        $menuJson = str_replace($term, $replacement, $menuJson);
                        $menu->items = json_decode($menuJson, true);
                        $menu->save();
                        $totalReplacements++;
                    }
                }
            } catch (\Exception $e) {
                // Skip silently
            }
        }

        // Replace in theme template files on disk
        $templateDirs = [
            resource_path('views/theme'),
            resource_path('views/vendor/cms-core'),
        ];
        foreach ($templateDirs as $dir) {
            if (is_dir($dir)) {
                try {
                    $files = File::allFiles($dir);
                    foreach ($files as $file) {
                        $content = $file->getContents();
                        if (str_contains($content, $term)) {
                            $newContent = str_replace($term, $replacement, $content);
                            File::put($file->getPathname(), $newContent);
                            $totalReplacements++;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Search & Replace: Error replacing in template files: ' . $e->getMessage());
                }
            }
        }

        // Clear caches
        try {
            Setting::flushCache();
        } catch (\Exception $e) {}
        try {
            Menu::flushCache();
        } catch (\Exception $e) {}

        $this->scan_results = null;
        $this->has_scanned = false;

        Notification::make()
            ->title("Replaced in {$totalReplacements} record(s)")
            ->body("All occurrences of \"{$term}\" have been replaced with \"{$replacement}\".")
            ->success()
            ->send();
    }
}
