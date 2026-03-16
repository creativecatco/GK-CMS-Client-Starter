<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use CreativeCatCo\GkCmsCore\Services\UpdateService;
use CreativeCatCo\GkCmsCore\Services\TemplateProtection;

class UpdatesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Updates';
    protected static ?string $title = 'Updates';
    protected static ?string $slug = 'updates';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'cms-core::filament.pages.updates';

    // Current install info
    public string $currentVersion = '';
    public string $updateChannel = '';

    // Latest available info
    public string $latestVersion = '';
    public string $latestDate = '';
    public string $latestNotes = '';
    public string $latestUrl = '';
    public string $downloadUrl = '';
    public bool $updateAvailable = false;

    // UI state
    public bool $checking = false;
    public string $updateLog = '';
    public array $preflightChecks = [];
    public array $modifiedTemplates = [];
    public string $changelog = '';

    public function mount(): void
    {
        $service = new UpdateService();
        $this->currentVersion = $service->getInstalledVersion();
        $this->updateChannel = $service->getChannel();

        // Load cached check results
        $cached = Cache::get('cms_update_check');
        if ($cached) {
            $this->latestVersion = $cached['latest_version'] ?? '';
            $this->latestDate = $cached['latest_date'] ?? '';
            $this->latestNotes = $cached['latest_notes'] ?? '';
            $this->latestUrl = $cached['latest_url'] ?? '';
            $this->downloadUrl = $cached['download_url'] ?? '';
            $this->updateAvailable = !empty($this->latestVersion)
                && version_compare($this->latestVersion, $this->currentVersion, '>');
        }

        // Load changelog
        $this->changelog = $service->getChangelog();

        // Check for modified templates
        $protection = new TemplateProtection();
        $this->modifiedTemplates = $protection->getModifiedTemplates();
    }

    /**
     * Check for the latest version.
     */
    public function checkForUpdates(): void
    {
        $this->checking = true;

        $service = new UpdateService();
        $latest = $service->checkLatestVersion();

        if ($latest) {
            $this->latestVersion = $latest['version'];
            $this->latestDate = $latest['date'];
            $this->latestNotes = $latest['notes'];
            $this->latestUrl = $latest['url'];
            $this->downloadUrl = $latest['download_url'] ?? '';

            $this->updateAvailable = version_compare($this->latestVersion, $this->currentVersion, '>');

            // Cache for 24 hours (daily check is sufficient)
            Cache::put('cms_update_check', [
                'latest_version' => $this->latestVersion,
                'latest_date' => $this->latestDate,
                'latest_notes' => $this->latestNotes,
                'latest_url' => $this->latestUrl,
                'download_url' => $this->downloadUrl,
            ], 86400);

            if ($this->updateAvailable) {
                Notification::make()
                    ->title('Update Available')
                    ->body("Version {$this->latestVersion} is available (you have {$this->currentVersion}).")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Up to Date')
                    ->body("You are running the latest version ({$this->currentVersion}).")
                    ->success()
                    ->send();
            }
        } else {
            $channel = $service->getChannel();
            $hint = $channel === 'composer'
                ? 'Make sure a GitHub Token is configured in Settings.'
                : 'Check your internet connection and try again.';

            Notification::make()
                ->title('Check Failed')
                ->body("Could not check for updates. {$hint}")
                ->danger()
                ->send();
        }

        $this->checking = false;
    }

    /**
     * Format the version for display (adds "v" prefix).
     */
    public function getDisplayVersionProperty(): string
    {
        if (empty($this->currentVersion) || $this->currentVersion === 'dev-main') {
            return 'dev-main';
        }
        return 'v' . $this->currentVersion;
    }

    /**
     * Format the latest version for display.
     */
    public function getDisplayLatestVersionProperty(): string
    {
        if (empty($this->latestVersion)) {
            return '';
        }
        return 'v' . $this->latestVersion;
    }
}
