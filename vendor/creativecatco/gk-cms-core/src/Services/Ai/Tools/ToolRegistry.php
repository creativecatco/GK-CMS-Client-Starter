<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

class ToolRegistry
{
    /**
     * @var ToolInterface[]
     */
    protected array $tools = [];

    /**
     * Register a tool.
     */
    public function register(ToolInterface $tool): self
    {
        $this->tools[$tool->name()] = $tool;
        return $this;
    }

    /**
     * Get a tool by name.
     */
    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Check if a tool exists.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get all registered tools.
     *
     * @return ToolInterface[]
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Get all tool definitions in OpenAI function calling format.
     *
     * @param array $exclude Tool names to exclude
     */
    public function getToolDefinitions(array $exclude = []): array
    {
        $tools = $this->tools;
        if (!empty($exclude)) {
            $tools = array_filter($tools, fn(ToolInterface $tool) => !in_array($tool->name(), $exclude));
        }
        return array_values(array_map(
            fn(ToolInterface $tool) => $tool->toToolDefinition(),
            $tools
        ));
    }

    /**
     * Get a lightweight set of tool definitions, excluding rarely-used tools.
     * This reduces token usage for rate-limited API plans while keeping
     * all critical tools available.
     *
     * ALWAYS included (core workflow tools):
     * - render_page: Essential for verifying changes work correctly
     * - read_error_log: Essential for debugging broken pages
     * - scan_website: Essential for analyzing the live site
     * - generate_image, upload_image: Core image features
     *
     * Excluded (rarely needed, can be added to conversation context on demand):
     * - create_plugin (~4K tokens) - only needed for plugin development
     * - run_query (~2K tokens) - advanced debugging
     * - read_file, write_file, list_files (~3K tokens) - file system tools
     * - run_artisan (~1K tokens) - advanced debugging
     */
    public function getLightweightToolDefinitions(): array
    {
        $heavyTools = [
            'create_plugin',
            'run_query',
            'read_file',
            'write_file',
            'list_files',
            'run_artisan',
        ];
        return $this->getToolDefinitions($heavyTools);
    }

    /**
     * Get tool names as a list.
     */
    public function getToolNames(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Create a registry with all default CMS tools registered.
     */
    public static function createDefault(): self
    {
        $registry = new self();

        // Read-only tools (no side effects)
        $registry->register(new GetSiteOverviewTool());
        $registry->register(new GetPageInfoTool());
        $registry->register(new GetFieldValueTool());
        $registry->register(new GetPageTemplateTool());
        $registry->register(new ListPagesTool());
        $registry->register(new GetThemeTool());
        $registry->register(new GetSettingsTool());
        $registry->register(new GetCssTool());

        // Page tools
        $registry->register(new CreatePageTool());
        $registry->register(new UpdatePageTemplateTool());
        $registry->register(new PatchPageTemplateTool());
        $registry->register(new UpdatePageFieldsTool());
        $registry->register(new DeletePageTool());
        $registry->register(new ImportHtmlTool());

        // Settings & theme tools
        $registry->register(new UpdateThemeTool());
        $registry->register(new UpdateSettingsTool());
        $registry->register(new UpdateCssTool());

        // Content tools
        $registry->register(new CreatePostTool());
        $registry->register(new CreatePortfolioTool());
        $registry->register(new CreateProductTool());

        // Navigation
        $registry->register(new UpdateMenuTool());

        // Media
        $registry->register(new UploadImageTool());
        $registry->register(new ListMediaTool());

        // Research & Analysis
        $registry->register(new ScanWebsiteTool());

        // Image Generation
        $registry->register(new GenerateImageTool());

        // Page Analysis
        $registry->register(new RenderPageTool());

        // SEO
        $registry->register(new SuggestSeoTool());

        // System / Debugging Tools
        $registry->register(new RunQueryTool());
        $registry->register(new ReadFileTool());
        $registry->register(new WriteFileTool());
        $registry->register(new ListFilesTool());
        $registry->register(new RunArtisanTool());
        $registry->register(new ReadErrorLogTool());

        // User Preferences (conversation memory)
        $registry->register(new SavePreferenceTool());
        $registry->register(new GetPreferencesTool());

        // Plugin System
        $registry->register(new CreatePluginTool());

        // Knowledge Library
        $registry->register(new GetKnowledgeTool());
        $registry->register(new ListKnowledgeTool());

        return $registry;
    }
}
