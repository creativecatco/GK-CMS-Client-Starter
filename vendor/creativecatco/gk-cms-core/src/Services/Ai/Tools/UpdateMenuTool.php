<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use CreativeCatCo\GkCmsCore\Models\Menu;

class UpdateMenuTool extends AbstractTool
{
    public function name(): string
    {
        return 'update_menu';
    }

    public function description(): string
    {
        return 'Update a navigation menu (header, footer, or footer_secondary). Each menu item has a label and URL. Items can have nested children for dropdown menus. This REPLACES the entire menu — include all desired items.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'enum' => ['header', 'footer', 'footer_secondary'],
                    'description' => 'The menu location to update.',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'Array of menu items. Each item has label, url, and optional children array for dropdowns.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => [
                                'type' => 'string',
                                'description' => 'The display text for the menu item.',
                            ],
                            'url' => [
                                'type' => 'string',
                                'description' => 'The URL the menu item links to. Use relative paths for internal pages (e.g., "/about", "/contact").',
                            ],
                            'children' => [
                                'type' => 'array',
                                'description' => 'Nested menu items for dropdown menus.',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'label' => ['type' => 'string'],
                                        'url' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['label', 'url'],
                    ],
                ],
            ],
            'required' => ['location', 'items'],
        ];
    }

    public function execute(array $params): array
    {
        $location = $params['location'];
        $items = $params['items'] ?? [];

        $validLocations = ['header', 'footer', 'footer_secondary'];
        if (!in_array($location, $validLocations)) {
            return $this->error("Invalid menu location: '{$location}'. Valid locations: " . implode(', ', $validLocations));
        }

        try {
            $menu = Menu::updateOrCreate(
                ['location' => $location],
                [
                    'name' => ucfirst(str_replace('_', ' ', $location)) . ' Menu',
                    'items' => $items,
                ]
            );

            return $this->success([
                'location' => $location,
                'item_count' => count($items),
                'items' => $items,
            ], ucfirst($location) . " menu updated with " . count($items) . " item(s).");
        } catch (\Exception $e) {
            return $this->error("Failed to update menu: {$e->getMessage()}");
        }
    }

    public function captureRollbackData(array $params): array
    {
        $location = $params['location'] ?? '';
        $menu = Menu::where('location', $location)->first();

        return [
            'location' => $location,
            'items' => $menu ? ($menu->items ?? []) : null,
            'existed' => (bool) $menu,
        ];
    }

    public function rollback(array $rollbackData): bool
    {
        $location = $rollbackData['location'] ?? '';

        if (!$rollbackData['existed']) {
            // Menu didn't exist before — delete it
            Menu::where('location', $location)->delete();
            return true;
        }

        // Restore previous items
        $menu = Menu::where('location', $location)->first();
        if ($menu) {
            $menu->update(['items' => $rollbackData['items'] ?? []]);
            return true;
        }

        return false;
    }
}
