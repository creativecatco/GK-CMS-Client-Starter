<?php

namespace CreativeCatCo\GkCmsCore\Console\Commands;

use CreativeCatCo\GkCmsCore\Services\DatabaseHealthCheck;
use Illuminate\Console\Command;

class DatabaseHealthCommand extends Command
{
    protected $signature = 'cms:health
        {--repair : Attempt to repair detected issues}
        {--auto : Skip confirmation prompts (use with --repair)}';

    protected $description = 'Check the health of the CMS database and optionally repair issues.';

    public function handle()
    {
        $healthCheck = new DatabaseHealthCheck();

        $this->info('');
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   GKeys CMS Database Health Check    ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->info('');

        $summary = $healthCheck->getSummary();

        if ($summary['healthy']) {
            $this->info('✓ No issues found. Your database is in good health!');
            $this->info('');
            return 0;
        }

        $this->warn("Found {$summary['issue_count']} issue(s) across " . count($summary['issues']) . " category(ies):");
        $this->info('');

        foreach ($summary['issues'] as $key => $issue) {
            $icon = ($issue['count'] ?? 0) > 0 ? '✗' : '⚠';
            $this->line("  {$icon} {$issue['label']}");
            $this->line("    {$issue['description']}");

            if (!empty($issue['count']) && $issue['count'] > 0) {
                $this->line("    Affected: {$issue['count']} item(s)");

                // Show up to 5 affected items
                $shown = 0;
                foreach ($issue['items'] ?? [] as $id => $name) {
                    if ($shown >= 5) {
                        $remaining = $issue['count'] - 5;
                        $this->line("    ... and {$remaining} more");
                        break;
                    }
                    $this->line("      - {$name}");
                    $shown++;
                }
            }
            $this->info('');
        }

        if ($this->option('repair')) {
            $shouldRepair = $this->option('auto') || $this->confirm('Do you want to attempt to repair these issues?');

            if ($shouldRepair) {
                $this->info('Starting repairs...');
                $this->info('');

                $results = $healthCheck->repairAll();

                foreach ($results as $key => $result) {
                    $label = $summary['issues'][$key]['label'] ?? $key;
                    $message = $result['message'] ?? "Repaired {$result['repaired']} of {$result['total']} items.";

                    if (!empty($result['error'])) {
                        $this->error("  ✗ {$label}: {$result['error']}");
                    } elseif ($result['repaired'] > 0) {
                        $this->info("  ✓ {$label}: {$message}");
                    } else {
                        $this->line("  - {$label}: No repairs needed.");
                    }
                }

                $this->info('');
                $this->info('Repair process completed. Run `php artisan cms:health` again to verify.');
            } else {
                $this->info('Repair cancelled.');
            }
        } else {
            $this->info('Run `php artisan cms:health --repair` to attempt to fix these issues.');
            $this->info('Add `--auto` to skip confirmation: `php artisan cms:health --repair --auto`');
        }

        $this->info('');
        return 1;
    }
}
