<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use CreativeCatCo\GkCmsCore\Models\Page;

class PageFieldsController extends Controller
{
    /**
     * Get all fields for a page.
     * GET /api/cms/pages/{slug}/fields
     */
    public function index(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return response()->json([
            'slug' => $page->slug,
            'title' => $page->title,
            'template' => $page->template,
            'fields' => $page->fields ?? [],
            'field_definitions' => $page->field_definitions ?? [],
        ]);
    }

    /**
     * Update specific fields for a page (partial update / merge).
     * PATCH /api/cms/pages/{slug}/fields
     *
     * Body: { "fields": { "hero_headline": "New Value", ... } }
     */
    public function update(Request $request, string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $request->validate([
            'fields' => 'required|array',
        ]);

        $existingFields = $page->fields ?? [];
        $newFields = array_merge($existingFields, $request->input('fields'));

        $page->fields = $newFields;
        $page->save();

        return response()->json([
            'message' => 'Fields updated successfully.',
            'fields' => $page->fields,
        ]);
    }

    /**
     * Replace all fields for a page (full overwrite).
     * PUT /api/cms/pages/{slug}/fields
     *
     * Body: { "fields": { ... } }
     */
    public function replace(Request $request, string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $request->validate([
            'fields' => 'required|array',
        ]);

        $page->fields = $request->input('fields');
        $page->save();

        return response()->json([
            'message' => 'Fields replaced successfully.',
            'fields' => $page->fields,
        ]);
    }

    /**
     * Get a single field value.
     * GET /api/cms/pages/{slug}/fields/{key}
     */
    public function show(string $slug, string $key): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        $fields = $page->fields ?? [];

        if (!array_key_exists($key, $fields)) {
            return response()->json(['message' => "Field '{$key}' not found."], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $fields[$key],
        ]);
    }

    /**
     * Update the custom template for a page and auto-discover fields.
     * PUT /api/cms/pages/{slug}/template
     *
     * Body: { "template": "<blade/html content>" }
     */
    public function updateTemplate(Request $request, string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $request->validate([
            'template' => 'required|string',
        ]);

        $page->template = 'custom';
        $page->custom_template = $request->input('template');

        // Auto-discover field definitions from the template
        $discovered = Page::discoverFieldsFromTemplate($page->custom_template);
        if (!empty($discovered)) {
            // Merge with existing definitions
            $existing = $page->field_definitions ?? [];
            $existingKeys = collect($existing)->pluck('key')->toArray();

            foreach ($discovered as $field) {
                if (!in_array($field['key'], $existingKeys)) {
                    $existing[] = $field;
                }
            }
            $page->field_definitions = $existing;
        }

        $page->save();

        return response()->json([
            'message' => 'Template updated and fields discovered.',
            'field_definitions' => $page->field_definitions,
            'discovered_count' => count($discovered),
        ]);
    }

    /**
     * Update field definitions for a page.
     * PUT /api/cms/pages/{slug}/field-definitions
     *
     * Body: { "field_definitions": [ { "key": "...", "label": "...", "type": "...", ... } ] }
     */
    public function updateFieldDefinitions(Request $request, string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $request->validate([
            'field_definitions' => 'required|array',
            'field_definitions.*.key' => 'required|string',
            'field_definitions.*.label' => 'required|string',
            'field_definitions.*.type' => 'required|string|in:' . implode(',', array_keys(Page::FIELD_TYPES)),
        ]);

        $page->field_definitions = $request->input('field_definitions');
        $page->save();

        return response()->json([
            'message' => 'Field definitions updated.',
            'field_definitions' => $page->field_definitions,
        ]);
    }

    /**
     * List all pages with their field counts.
     * GET /api/cms/pages
     */
    public function listPages(): JsonResponse
    {
        $pages = Page::select('id', 'title', 'slug', 'template', 'status', 'updated_at')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'template' => $page->template,
                    'status' => $page->status,
                    'updated_at' => $page->updated_at->toISOString(),
                ];
            });

        return response()->json(['pages' => $pages]);
    }

    /**
     * Create a new page with template and fields.
     * POST /api/cms/pages
     *
     * Body: { "title": "...", "slug": "...", "template": "custom", "custom_template": "...", "fields": {...}, "status": "published" }
     */
    public function createPage(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'template' => 'string',
            'custom_template' => 'nullable|string',
            'fields' => 'nullable|array',
            'field_definitions' => 'nullable|array',
            'status' => 'string|in:draft,published',
        ]);

        $page = Page::create([
            'title' => $request->input('title'),
            'slug' => $request->input('slug'),
            'template' => $request->input('template', 'custom'),
            'custom_template' => $request->input('custom_template'),
            'fields' => $request->input('fields', []),
            'field_definitions' => $request->input('field_definitions', []),
            'status' => $request->input('status', 'draft'),
        ]);

        // Auto-discover fields if custom template provided
        if ($page->template === 'custom' && !empty($page->custom_template)) {
            $discovered = Page::discoverFieldsFromTemplate($page->custom_template);
            if (!empty($discovered) && empty($page->field_definitions)) {
                $page->field_definitions = $discovered;
                $page->saveQuietly();
            }
        }

        return response()->json([
            'message' => 'Page created successfully.',
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'template' => $page->template,
                'field_definitions' => $page->field_definitions,
            ],
        ], 201);
    }
}
