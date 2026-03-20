# Changelog

All notable changes to GKeys CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.8.7.2] - 2026-03-19

### Fixed
- **ZIP/HTML file persistence: Storage::disk('public') as primary strategy**: All previous filesystem persistence methods (`file_put_contents`, `copy`, `stream_copy`, `rename`) were failing on SiteGround shared hosting. Now uses `Storage::disk('public')` as the FIRST persistence strategy — this is the same method that successfully handles image uploads on SiteGround, writing to `storage/app/public/cms/imports/`. This is proven to work because CMS image uploads use the exact same disk.
- **Laravel Cache as ultimate fallback**: If ALL filesystem methods fail (including Storage facade), the file content is stored in Laravel Cache (database-backed) as base64. The `resolveImportId()` method can retrieve it from cache and write to a temp file for the import tool.
- **resolveImportId() now checks Storage::disk('public') first**: The resolution order is now: Storage::disk('public') → Storage::disk('local') → manifest files → predictable paths → Cache (database) → glob search.
- **resolveZipPath() updated**: Now also searches `storage/app/public/cms/imports/` and uses `Storage::disk('public')` for temp file persistence.

## [0.8.7.1] - 2026-03-19

### Fixed
- **Version display stuck on 0.8.6.7**: The `config/cms.php` version was never updated in previous releases. Now properly set to 0.8.7.1. The `getInstalledVersion()` method reads from this file first, so it was always showing the old version.
- **File persistence reliability overhaul**: 
  - `extractZip()` and `extractHtml()` now read the entire file into memory IMMEDIATELY upon upload, before any persistence attempts. This ensures the data is available even if the temp file is cleaned up.
  - Added emergency save path: if `persistWithImportId()` returns the temp path (meaning all 12 attempts failed), tries `file_put_contents()` with `0777` permissions, then falls back to Laravel's `Storage` facade.
  - `persistWithImportId()` now prioritizes `file_put_contents()` with in-memory content over `copy()` from disk, since the source file may be gone.
  - Added detailed error logging for every failed persistence attempt, including the specific error message, directory permissions, and storage path diagnostics.

## [0.8.7.0] - 2026-03-19

### Changed
- **BREAKING: import_id replaces storage_path for ZIP/HTML imports** — The `import_zip_site` and `import_html_page` tools now accept an `import_id` parameter (e.g., `zip_a1b2c3d4`) instead of a full filesystem path. This eliminates the root cause of repeated "file not found" errors where the AI hallucinated or mangled the storage path.

### Fixed
- **ZIP/HTML Import Architecture Overhaul**: Completely redesigned the file upload → import pipeline:
  - `FileExtractor` now generates a short, unique `import_id` for each uploaded file
  - Files are saved with predictable names (`{import_id}.zip`) in `storage/app/zip-imports/`
  - A manifest file (`manifest.json`) maps import IDs to actual file paths
  - `resolveImportId()` provides 3-layer lookup: manifest → predictable path → glob search
  - The AI only needs to copy a short 12-character string, not a full absolute path
- **Multi-directory persistence**: `persistWithImportId()` tries 4 different directories (zip-imports, app, framework/cache, sys_temp) × 3 copy strategies (copy, file_put_contents, stream_copy) = 12 attempts before giving up
- **Backward compatible**: `storage_path` parameter still works as a deprecated fallback

## [0.8.6.9] - 2026-03-19

### Fixed
- **ZIP/HTML Import: Livewire Temp File Handling**: On shared hosting (e.g., SiteGround), uploaded files are stored in Livewire temp directories (`storage/framework/cache/temp-files/`) instead of standard PHP temp. The `FileExtractor` now uses a robust `persistUploadedFile()` method with 4 fallback strategies (copy → file_put_contents → stream_copy → rename) to ensure files are persisted to `storage/app/zip-imports/` or `storage/app/html-imports/`
- **Expanded Path Resolution**: `ImportZipTool` and `ImportHtmlTool` now search Livewire temp directories, `storage/framework/cache/temp-files/`, and `sys_get_temp_dir()` when the provided path doesn't exist. Found temp files are automatically persisted to stable storage before import
- **Graceful Degradation**: If all persistence methods fail, the tools now fall back to using the source temp path directly rather than returning an error, giving the import the best chance of succeeding

## [0.8.6.8] - 2026-03-19

### Fixed
- **ZIP/HTML Import Path Resolution**: AI assistant sometimes used incorrect file paths when calling import tools. Added smart fallback path resolution that searches `storage/app/zip-imports/` and `storage/app/html-imports/` for recently uploaded files when the exact path doesn't match
- Made file upload metadata more emphatic about using the exact storage path to reduce AI hallucination
- Updated system prompt with explicit instructions to copy storage paths verbatim

## [0.8.6.7] - 2026-03-19

### Fixed
- Changelog now shows all version history (previously stopped at v0.7.1)
- Changelog dynamically fetches latest release notes from GitHub so users can see what's new in available updates before installing

## [0.8.6.6] - 2026-03-19

### Added
- **ZIP Import Tool** (`import_zip_site`): Upload a `.zip` archive containing multiple HTML pages and images to import an entire site at once
  - All images saved to CMS media library with automatic path rewriting in HTML
  - External CSS files merged into page-specific CSS
  - Processes pages sequentially to avoid memory issues (handles 20+ pages)
  - macOS `__MACOSX` metadata folders automatically skipped
- **Header Nav Conversion**: Imported headers automatically have hardcoded `<a>` links converted to dynamic CMS menu items, so `update_menu` works immediately
- **Header/Footer Protection**: `patch_page_template` and `update_page_template` now block dangerous Blade code injection (`@foreach`, `$variable`, `{!!`) on header/footer pages
- FileExtractor now handles `.zip` file uploads

### Changed
- Updated knowledge modules and system prompt for new tools

## [0.8.6.5] - 2026-03-19

### Fixed
- Registered `html-to-cms-conversion` and `design-library` in the knowledge topic registry (previously returned "Unknown topic" errors)
- AI no longer answers its own questions — added explicit stop-and-wait instructions in tool responses, system prompt, and orchestrator completion detection

## [0.8.6.4] - 2026-03-19

### Added
- **Header/Footer Display Scope**: Headers and footers can now target All Pages (default), Specific Pages, or Specific Page Types (Blog, Portfolio, Products, etc.)
- Priority matching: page-specific headers win over type-specific, which win over "all pages" default
- Admin UI: Display Settings section appears when editing header/footer pages
- Database migration for `display_scope` and `display_on` columns

### Changed
- `import_html_page` now always strips header/footer from the page body and asks the user before importing them as site-wide components
- Import tool supports `import_header` and `import_footer` parameters for explicit control

## [0.8.6.3] - 2026-03-19

### Fixed
- **CSS Scoping**: All imported page CSS is now scoped under `.imported-page` wrapper to prevent conflicts with Tailwind/CMS layout resets
- `body` and `html` CSS selectors remapped to `.imported-page` wrapper
- Tailwind preflight "undo" block restores browser defaults for headings, paragraphs, links, and lists within imported pages

### Added
- **Header/Footer Extraction**: `import_html_page` automatically detects `<header>` and `<footer>` tags, splits them into separate CMS page records
- Layout now loads `custom_css` from header and footer page records (previously only loaded main page CSS)
- `<script>` tags stripped from imported body HTML to prevent JS conflicts

## [0.8.6.2] - 2026-03-19

### Fixed
- **HTML upload no longer hangs**: FileExtractor returns metadata-only for HTML files (not full raw HTML), preventing the 10K message size limit from being exceeded
- Storage directory created with `mkdir` instead of Laravel Storage facade for shared hosting compatibility

### Added
- **Drag-and-drop file upload**: Drop files anywhere on the AI chat panel
- Attach button now opens file picker directly (removed intermediate dropdown popup)
- Unified file input accepts all supported types (HTML, PDF, images, documents)

## [0.8.6.1] - 2026-03-19

### Added
- **ImportHtmlTool** (`import_html_page`): Dedicated PHP tool that programmatically converts static HTML files into CMS pages
  - Preserves CSS byte-for-byte (extracted from `<style>` tags)
  - Preserves HTML structure byte-for-byte (only adds `data-field` attributes)
  - Auto-discovers 80+ editable fields (headings, paragraphs, buttons, images, list items)
  - Handles HTML entity decoding automatically
  - One tool call does the entire conversion

### Fixed
- FileExtractor no longer strips CSS and HTML tags from uploaded HTML files
- Raw HTML preserved and saved to disk for ImportHtmlTool to access

## [0.8.6.0] - 2026-03-19

### Added
- **HTML-to-CMS Conversion** knowledge module with step-by-step conversion workflow
- **Button Custom Colors**: `renderButton()` supports `custom_color` and `custom_text_color` with auto-contrast detection
- **Design Library** knowledge module with advanced section patterns (hero, feature grid, testimonials)

### Changed
- Updated system prompt with HTML conversion guidance and button color rules
- Updated `website-recreation.md`, `css-variables.md`, and `button-fields.md` knowledge modules

## [0.8.4] - 2026-03-18

### Changed
- AI thinking process improvements with better safeguards and error handling
- Enhanced tool intelligence for more reliable page editing

## [0.8.3] - 2026-03-18

### Added
- **Image Generation**: Support for DALL-E / GPT Image, Pollinations AI, and smart provider auto-selection
- Automatic fallback chain across image providers
- Retry logic for rate-limited requests
- Image preview thumbnails in AI chat with clickable links
- Collapsible HTML/code in tool details (auto-collapses large payloads)

### Fixed
- Dark mode: black-on-black text on Updates page
- Dark mode: green/amber status card text visibility

## [0.7.3] - 2026-03-17

### Fixed
- Various stability improvements and bug fixes

## [0.7.1] - 2026-03-16

### Fixed
- Fixed fatal error in AI tools: SavePreferenceTool, GetPreferencesTool, and CreatePluginTool had incorrect method names
- Fixed execute() method signature mismatch in preference and plugin tools
- AI chat now works correctly — the fatal error was preventing all AI interactions

### Changed
- Template protection now excludes admin views from being published as overrides
- Admin views always load directly from the package, ensuring updates take effect immediately
- safePublish() now automatically cleans up stale published admin views during updates

## [0.7.0] - 2026-03-16

### Added
- API Settings page in admin for configuring AI provider keys
- Setup Wizard for first-time configuration
- Improved AI provider management

## [0.6.0] - 2026-03-16

### Added
- Planning UI for AI conversations
- Conversation memory and context persistence
- Improved AI workflow management

## [0.5.0] - 2026-03-15

### Added
- Agentic AI: multi-step tool execution with planning and reasoning
- AI can now chain multiple tools to complete complex tasks

## [0.4.0] - 2026-03-15

### Added
- Website Scanner tool for AI to analyze existing sites
- Image upload and management via AI chat
- Enhanced AI tool descriptions and parameters

## [0.3.9.1] - 2026-03-15

### Added
- AI file upload support (PDF, documents, images)
- Service provider improvements

## [0.3.8.1] - 2026-03-15

### Fixed
- Header dropdown hover behavior fix

## [0.3.0] - 2026-03-14

### Added
- Dual update channel support: 'composer' (development) and 'release' (customer/production)
- Release channel: downloads pre-built zips from GitHub Releases — no GitHub token needed
- Single-file web installer (install.php) with setup wizard
- GitHub Actions build pipeline for automated release zip creation

### Changed
- Update system now supports both Composer-based and zip-based updates
- Customer installs default to 'release' channel (no Composer/GitHub token required)

## [0.2.0] - 2026-03-14

### Added
- One-click update system with background processing and live log output
- Semantic versioning with proper version display in admin panel
- Template protection: customer-edited templates are never overwritten during updates
- Content protection: database content is never touched by updates
- CHANGELOG.md for release notes display in admin

### Fixed
- Update process no longer times out on shared hosting

## [0.1.0] - 2026-03-13

### Added
- Initial CMS core package
- Page management with custom templates and fields
- Blog system with categories and tags
- Portfolio and Products content types
- Menu management (header and footer)
- Media library with image and video uploads
- Filament admin panel integration
- Theme builder with color and font settings
- Global CSS editor
- SEO fields on all content types
- Inline editor for front-end editing
- Contact form with email notifications
- AI conversation and action models (foundation)
- LLM provider adapters (OpenAI, Anthropic, Google, xAI, Manus)
- Default content seeder for fresh installs
- SiteGround shared hosting compatibility
