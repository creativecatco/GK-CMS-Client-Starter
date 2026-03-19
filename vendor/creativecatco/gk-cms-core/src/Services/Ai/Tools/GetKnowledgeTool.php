<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

class GetKnowledgeTool extends AbstractTool
{
    /**
     * Available knowledge modules with descriptions.
     */
    protected const MODULES = [
        'field-types' => [
            'description' => 'Complete reference for all CMS field types (text, textarea, richtext, image, button, section_bg, repeater, icon, etc.) with template syntax and update formats.',
            'use_when' => 'Creating or editing templates, updating field values, understanding field data formats.',
        ],
        'template-rules' => [
            'description' => 'Template structure, Blade syntax, layout provides, inline editing attributes, section patterns, responsive design conventions.',
            'use_when' => 'Creating new pages, modifying templates, adding sections.',
        ],
        'icon-library' => [
            'description' => 'Complete list of available icon names and the SVG rendering script that MUST be included in templates using icons.',
            'use_when' => 'Creating templates with icon fields, looking up available icon names.',
        ],
        'image-workflow' => [
            'description' => 'Complete workflow for generating, uploading, and placing images on pages. Covers generate_image parameters, image vs section_bg field handling, and verification steps.',
            'use_when' => 'Generating images, placing images on pages, changing existing images.',
        ],
        'section-bg' => [
            'description' => 'Detailed documentation for the section_bg field type — JSON object format, properties, modes, overlays, and how to update correctly.',
            'use_when' => 'Working with section backgrounds, changing hero images, updating background colors/gradients/overlays.',
        ],
        'css-variables' => [
            'description' => 'CSS variable reference (--color-primary, --color-secondary, etc.), usage rules, and how theme settings map to variables.',
            'use_when' => 'Styling pages, changing colors/fonts, creating templates.',
        ],
        'page-building' => [
            'description' => 'Full website build workflow, common section patterns (hero, features, CTA, contact, testimonials) with complete template snippets, and page checklist.',
            'use_when' => 'Building new pages from scratch, building full websites, creating landing pages.',
        ],
        'website-recreation' => [
            'description' => 'Workflow for recreating websites from URLs or static HTML files. Covers scan_website usage, content mapping, image handling, and HTML-to-CMS conversion.',
            'use_when' => 'User provides a URL to recreate, a static HTML file to convert, or wants to match an existing design.',
        ],
        'debugging' => [
            'description' => 'Error diagnosis sequence, common error patterns (Blade syntax, field format, broken header/footer), and fix strategies.',
            'use_when' => 'Something is broken, user reports an error, page shows blank or 500 error.',
        ],
        'seo-best-practices' => [
            'description' => 'SEO requirements for pages — title tags, meta descriptions, heading hierarchy, image alt text, URL structure, and suggest_seo tool usage.',
            'use_when' => 'Creating pages (always set SEO fields), user asks about SEO.',
        ],
        'repeater-fields' => [
            'description' => 'Repeater field format, template syntax with @foreach and dot notation, common patterns (services grid, FAQ accordion, team members, pricing plans).',
            'use_when' => 'Creating templates with repeating items (services, team, features, testimonials, FAQ).',
        ],
        'button-fields' => [
            'description' => 'Button and button_group field types — renderButton() usage (CRITICAL: not button()), styles, data format, and template patterns.',
            'use_when' => 'Adding buttons to templates, updating button text/links/styles.',
        ],
        'content-types' => [
            'description' => 'Posts, portfolios, and products — database columns, URL structure, creation tools, and feature toggles.',
            'use_when' => 'Creating blog posts, portfolio items, products, or managing non-page content.',
        ],
        'plugin-development' => [
            'description' => 'Plugin scaffolding with create_plugin, directory structure, custom routes/controllers/models, and migration creation.',
            'use_when' => 'User needs custom functionality, custom routes, or anything beyond standard CMS features.',
        ],
        'html-to-cms-conversion' => [
            'description' => 'Complete workflow for importing static HTML files as CMS pages using the import_html_page tool. Covers CSS scoping, header/footer extraction, field injection, and post-import editing.',
            'use_when' => 'User uploads an HTML file to convert into a CMS page, or asks to replicate a static HTML page.',
        ],
        'design-library' => [
            'description' => 'Advanced section patterns and design principles for building high-quality pages — hero sections, feature grids, testimonials, CTAs with production-ready Blade/Tailwind code.',
            'use_when' => 'Building new pages from scratch and wanting professional, polished designs.',
        ],
    ];

    public function name(): string
    {
        return 'get_knowledge';
    }

    public function description(): string
    {
        return <<<'DESC'
Load detailed documentation about a specific CMS topic. Call this BEFORE performing any complex task to ensure you have the correct syntax, formats, and workflows.

Available topics: field-types, template-rules, icon-library, image-workflow, section-bg, css-variables, page-building, website-recreation, debugging, seo-best-practices, repeater-fields, button-fields, content-types, plugin-development, html-to-cms-conversion, design-library

Simple tasks (changing text, updating a single field) do NOT need knowledge modules. Only load what you need for the current task.
DESC;
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic' => [
                    'type' => 'string',
                    'description' => 'The knowledge module to load.',
                    'enum' => array_keys(self::MODULES),
                ],
            ],
            'required' => ['topic'],
        ];
    }

    public function execute(array $params): array
    {
        $topic = $params['topic'] ?? '';

        if (empty($topic)) {
            return $this->error('The "topic" parameter is required. Use list_knowledge to see available topics.');
        }

        if (!isset(self::MODULES[$topic])) {
            $available = implode(', ', array_keys(self::MODULES));
            return $this->error("Unknown topic: '{$topic}'. Available topics: {$available}");
        }

        // Look for the knowledge file
        $paths = [
            // Published (customizable) version
            resource_path("cms/knowledge/{$topic}.md"),
            // Package bundled version
            __DIR__ . '/../../../../resources/prompts/knowledge/' . $topic . '.md',
        ];

        $content = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                break;
            }
        }

        if ($content === null) {
            return $this->error("Knowledge module file not found for topic: '{$topic}'. The module may not be installed correctly.");
        }

        return $this->success([
            'topic' => $topic,
            'description' => self::MODULES[$topic]['description'],
            'content' => $content,
        ], "Loaded knowledge module: {$topic}. Use this information to complete the current task correctly.");
    }
}
