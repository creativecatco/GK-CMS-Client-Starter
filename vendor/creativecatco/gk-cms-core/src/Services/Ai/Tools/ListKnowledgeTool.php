<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

class ListKnowledgeTool extends AbstractTool
{
    public function name(): string
    {
        return 'list_knowledge';
    }

    public function description(): string
    {
        return 'List all available knowledge modules with descriptions and when to use them. Call this when you are unsure which knowledge module to load for a task.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(), // No parameters
            'required' => [],
        ];
    }

    public function execute(array $params): array
    {
        // Reuse the module definitions from GetKnowledgeTool
        $modules = [
            'field-types' => [
                'description' => 'All CMS field types with template syntax and update formats.',
                'use_when' => 'Creating/editing templates, updating field values.',
            ],
            'template-rules' => [
                'description' => 'Template structure, Blade syntax, inline editing, section patterns.',
                'use_when' => 'Creating new pages, modifying templates.',
            ],
            'icon-library' => [
                'description' => 'Available icon names and the required SVG rendering script.',
                'use_when' => 'Templates with icon fields.',
            ],
            'image-workflow' => [
                'description' => 'Generate, upload, and place images on pages.',
                'use_when' => 'Generating/changing images on pages.',
            ],
            'section-bg' => [
                'description' => 'section_bg JSON format, properties, modes, overlays.',
                'use_when' => 'Section backgrounds, hero images, overlays.',
            ],
            'css-variables' => [
                'description' => 'CSS variable reference and theme mapping.',
                'use_when' => 'Styling, colors, fonts.',
            ],
            'page-building' => [
                'description' => 'Full build workflow and section pattern templates.',
                'use_when' => 'Building new pages or full websites.',
            ],
            'website-recreation' => [
                'description' => 'Recreate from URL or static HTML conversion.',
                'use_when' => 'Recreating/matching existing websites.',
            ],
            'debugging' => [
                'description' => 'Error diagnosis, common patterns, fix strategies.',
                'use_when' => 'Broken pages, errors, 500s.',
            ],
            'seo-best-practices' => [
                'description' => 'SEO titles, descriptions, heading hierarchy, alt text.',
                'use_when' => 'Creating pages, SEO optimization.',
            ],
            'repeater-fields' => [
                'description' => 'Repeater format, @foreach syntax, common patterns.',
                'use_when' => 'Lists of items (services, team, FAQ, pricing).',
            ],
            'button-fields' => [
                'description' => 'Button/button_group format and renderButton() usage.',
                'use_when' => 'Adding or updating buttons.',
            ],
            'content-types' => [
                'description' => 'Posts, portfolios, products — columns and tools.',
                'use_when' => 'Creating non-page content.',
            ],
            'plugin-development' => [
                'description' => 'Plugin scaffolding and custom code patterns.',
                'use_when' => 'Custom functionality beyond CMS features.',
            ],
        ];

        $list = [];
        foreach ($modules as $topic => $info) {
            $list[] = [
                'topic' => $topic,
                'description' => $info['description'],
                'use_when' => $info['use_when'],
            ];
        }

        return $this->success([
            'modules' => $list,
            'total' => count($list),
            'usage' => 'Call get_knowledge(topic: "module-name") to load a specific module.',
        ], 'Found ' . count($list) . ' knowledge modules. Load the ones relevant to your current task.');
    }
}
