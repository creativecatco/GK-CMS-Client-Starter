<?php

namespace CreativeCatCo\GkCmsCore\Console\Commands;

use Illuminate\Console\Command;
use CreativeCatCo\GkCmsCore\Services\TemplateProtection;

class SafePublishTemplates extends Command
{
    protected $signature = 'cms:safe-publish-templates';
    protected $description = 'Publish CMS template files while protecting customer-modified templates and cleaning up stale admin views';

    public function handle(): int
    {
        $this->info('Publishing templates with customer protection...');

        $protection = new TemplateProtection();
        $result = $protection->safePublish();

        if (!empty($result['cleaned'])) {
            $this->info('  Stale admin views cleaned up: ' . count($result['cleaned']));
            foreach ($result['cleaned'] as $file) {
                $this->line("    - {$file} (removed — admin views load from package)");
            }
        }

        if (!empty($result['new'])) {
            $this->info('  New templates added: ' . count($result['new']));
            foreach ($result['new'] as $file) {
                $this->line("    + {$file}");
            }
        }

        if (!empty($result['updated'])) {
            $this->info('  Templates updated: ' . count($result['updated']));
        }

        if (!empty($result['skipped'])) {
            $this->warn('  Templates skipped (customer-modified): ' . count($result['skipped']));
            foreach ($result['skipped'] as $file) {
                $this->line("    ~ {$file} (preserved customer edits)");
            }
        }

        $total = count($result['new']) + count($result['updated']) + count($result['skipped']);
        $this->info("Template publish complete. {$total} templates processed, " . count($result['cleaned']) . " stale admin views cleaned.");

        return Command::SUCCESS;
    }
}
