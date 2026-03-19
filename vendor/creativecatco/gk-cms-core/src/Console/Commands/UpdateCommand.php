<?php

namespace CreativeCatCo\GkCmsCore\Console\Commands;

use Illuminate\Console\Command;
use CreativeCatCo\GkCmsCore\Services\UpdateService;

class UpdateCommand extends Command
{
    protected $signature = 'cms:update {--channel= : Update channel (composer or release)}';
    protected $description = 'Update the GKeys CMS Core package via composer update.';

    public function handle()
    {
        $updateService = new UpdateService();
        $channel = $updateService->getChannel();

        $this->info("GKeys CMS Update");
        $this->info("Channel: {$channel}");
        $this->info("Current version: " . $updateService->getInstalledVersion());
        $this->newLine();

        // Pre-flight checks
        $this->info("Running pre-flight checks...");
        $preflight = $updateService->preFlightChecks();
        foreach ($preflight['checks'] as $check) {
            $icon = $check['pass'] ? '✓' : '✗';
            $this->line("  {$icon} {$check['label']}: {$check['message']}");
        }
        $this->newLine();

        if (!$preflight['pass']) {
            $this->error("Pre-flight checks failed. Please fix the issues above before updating.");
            return 1;
        }

        // Check for latest version
        $this->info("Checking for updates...");
        $latest = $updateService->checkLatestVersion();

        if (!$latest) {
            $this->warn("Could not check for the latest version. Proceeding with update anyway...");
        } else {
            $this->info("Latest available: v{$latest['version']} ({$latest['date']})");
            $this->newLine();
        }

        if (!$this->confirm('Do you want to proceed with the update?', true)) {
            $this->info("Update cancelled.");
            return 0;
        }

        // Start the update
        $downloadUrl = $latest['download_url'] ?? null;
        $result = $updateService->startUpdate($downloadUrl);

        if (!$result['success']) {
            $this->error("Update failed to start: {$result['message']}");
            return 1;
        }

        $this->info("Update started in background.");
        $this->info("Monitor progress with: tail -f " . storage_path('logs/cms-update.log'));

        // Poll for completion
        $maxWait = 300; // 5 minutes
        $waited = 0;
        $this->newLine();
        $this->info("Waiting for update to complete...");

        while ($waited < $maxWait) {
            sleep(3);
            $waited += 3;
            $status = $updateService->getStatus();

            if ($status['status'] === 'complete') {
                $this->newLine();
                $this->info("Update completed successfully!");
                $this->info("New version: " . $updateService->getInstalledVersion());
                return 0;
            }

            if ($status['status'] === 'failed') {
                $this->newLine();
                $this->error("Update failed. Check the log:");
                $this->line($status['log']);
                return 1;
            }

            // Show progress dots
            $this->output->write('.');
        }

        $this->newLine();
        $this->warn("Update is still running after {$maxWait} seconds.");
        $this->info("Check status with: tail -f " . storage_path('logs/cms-update.log'));
        return 0;
    }
}
