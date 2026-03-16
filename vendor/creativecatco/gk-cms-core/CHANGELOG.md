# Changelog

All notable changes to GKeys CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.1] - 2026-03-16

### Fixed
- Fixed fatal error in AI tools: SavePreferenceTool, GetPreferencesTool, and CreatePluginTool had incorrect method names (getName/getDescription/getParameters instead of name/description/parameters) that didn't match the ToolInterface contract
- Fixed execute() method signature mismatch in preference and plugin tools (removed extra userId parameter)
- AI chat now works correctly — the fatal error was preventing all AI interactions

### Changed
- Template protection now excludes admin views (filament/*, errors/*) from being published as overrides
- Admin views always load directly from the package, ensuring updates take effect immediately
- safePublish() now automatically cleans up stale published admin views during updates
- SafePublishTemplates command now reports cleaned up stale admin view files
- Prevents future issues where old published admin views override new package features (e.g., file upload button not appearing)

## [0.3.0] - 2025-03-14

### Added
- Dual update channel support: 'composer' (development) and 'release' (customer/production)
- Release channel: downloads pre-built zips from GitHub Releases — no GitHub token needed
- Single-file web installer (install.php) with setup wizard for customer deployments
- GitHub Actions build pipeline for automated release zip creation
- System requirements checker in installer (PHP, extensions, disk space, permissions)
- Database connection tester in installer
- Automatic admin account creation during install
- Update channel indicator in admin panel Updates page

### Changed
- Update system now supports both Composer-based and zip-based updates
- Pre-flight checks are channel-aware (different requirements per channel)
- Customer installs default to 'release' channel (no Composer/GitHub token required)
- Development installs use 'composer' channel for direct package updates

## [0.2.0] - 2025-03-14

### Added
- One-click update system with background processing and live log output
- Semantic versioning with proper version display in admin panel
- Template protection: customer-edited templates are never overwritten during updates
- Content protection: database content (pages, posts, products, etc.) is never touched by updates
- Template hash tracking to detect customer modifications
- Update pre-flight checks (PHP version, disk space, permissions)
- Post-update version verification
- Stale lock file recovery (auto-cleans after 10 minutes)
- CHANGELOG.md for release notes display in admin

### Changed
- Updates page now shows version numbers (e.g., v0.2.0) instead of commit hashes
- Update check now uses GitHub Releases/Tags API instead of raw commits
- Improved error handling and recovery in the update pipeline

### Fixed
- Update process no longer times out on shared hosting (runs in background)
- Removed old synchronous applyUpdate() method that caused browser timeouts

## [0.1.0] - 2025-03-13

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
- Settings management
- AI conversation and action models (foundation)
- LLM provider adapters (OpenAI, Anthropic, Google, xAI, Manus)
- Default content seeder for fresh installs
- SiteGround shared hosting compatibility
