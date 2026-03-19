<?php

namespace CreativeCatCo\GkCmsCore\Filament\Pages;

use CreativeCatCo\GkCmsCore\Services\DatabaseHealthCheck;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class DatabaseHealthPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $slug = 'database-health';

    protected static string $view = 'cms-core::filament.pages.database-health-page';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'Database Health';

    protected static ?int $navigationSort = 4;

    public array $issues = [];

    public array $repairResults = [];

    public bool $isHealthy = true;

    public int $issueCount = 0;

    public function mount(): void
    {
        $this->runHealthCheck();
    }

    public function runHealthCheck(): void
    {
        $healthCheck = new DatabaseHealthCheck();
        $summary = $healthCheck->getSummary();

        $this->issues = $summary['issues'];
        $this->isHealthy = $summary['healthy'];
        $this->issueCount = $summary['issue_count'];
        $this->repairResults = [];
    }

    public function repairAll(): void
    {
        $healthCheck = new DatabaseHealthCheck();
        $this->repairResults = $healthCheck->repairAll();

        // Re-run health check after repair
        $this->runHealthCheck();

        Notification::make()
            ->title('Repair Complete')
            ->body('Database repair process has completed. Check the results below.')
            ->success()
            ->send();
    }

    public function repairIssue(string $issueKey): void
    {
        $healthCheck = new DatabaseHealthCheck();
        $this->repairResults = $healthCheck->repairIssues([$issueKey]);

        // Re-run health check after repair
        $this->runHealthCheck();

        Notification::make()
            ->title('Repair Complete')
            ->body("Repair for '{$issueKey}' has completed.")
            ->success()
            ->send();
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $healthCheck = new DatabaseHealthCheck();
            $summary = $healthCheck->getSummary();
            return $summary['issue_count'] > 0 ? (string) $summary['issue_count'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $healthCheck = new DatabaseHealthCheck();
            $summary = $healthCheck->getSummary();
            return $summary['issue_count'] > 0 ? 'danger' : 'success';
        } catch (\Exception $e) {
            return null;
        }
    }
}
