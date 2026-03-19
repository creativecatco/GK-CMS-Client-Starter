<?php

namespace CreativeCatCo\GkCmsCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use CreativeCatCo\GkCmsCore\Models\Page;
use CreativeCatCo\GkCmsCore\Services\DatabaseHealthCheck;

class TestDatabaseHealth extends Command
{
    protected $signature = 'cms:health-test';
    protected $description = 'Run a test of the database health check and repair functionality.';

    public function handle()
    {
        $healthCheck = new DatabaseHealthCheck();

        $this->info('Starting Database Health Check Test...');
        $this->info('');

        // 1. Create dummy data with issues
        $this->info('1. Creating dummy pages with issues...');

        $pageWithMissingSlug = Page::create([
            'title' => 'Test Page Missing Slug',
            'slug' => '',
            'status' => 'draft',
        ]);
        $this->line("   Created page ID {$pageWithMissingSlug->id} with empty slug.");

        // Determine the correct column for custom template
        $templateColumn = Schema::hasColumn('pages', 'custom_template') ? 'custom_template' : 'template_code';
        $pageWithMalformedTemplate = Page::create([
            'title' => 'Test Page Malformed Template',
            'slug' => 'test-page-malformed-' . time(),
            'status' => 'draft',
            $templateColumn => '<?php echo "malicious code"; ?>',
        ]);
        $this->line("   Created page ID {$pageWithMalformedTemplate->id} with malformed template.");

        // 2. Run initial health check
        $this->info('');
        $this->info('2. Running initial health check...');
        $issues = $healthCheck->getIssues();

        if (empty($issues)) {
            $this->error('   FAIL: No issues were detected when they should have been.');
            $this->cleanup($pageWithMissingSlug, $pageWithMalformedTemplate);
            return 1;
        }

        $this->info('   Issues detected:');
        foreach ($issues as $key => $issue) {
            $this->line("   - {$issue['label']}: {$issue['count']} item(s)");
        }

        // 3. Run the repair process
        $this->info('');
        $this->info('3. Running the repair process...');
        $repairResults = $healthCheck->repairAll();

        foreach ($repairResults as $key => $result) {
            $message = $result['message'] ?? "Repaired {$result['repaired']} of {$result['total']}";
            $this->line("   - {$key}: {$message}");
        }

        // 4. Run final health check
        $this->info('');
        $this->info('4. Running final health check to verify fixes...');
        $finalIssues = $healthCheck->getIssues();

        // Filter out non-test issues (pre-existing)
        $remainingTestIssues = false;
        if (!empty($finalIssues)) {
            foreach ($finalIssues as $key => $issue) {
                if (!empty($issue['items'])) {
                    foreach ($issue['items'] as $id => $name) {
                        if ($id == $pageWithMissingSlug->id || $id == $pageWithMalformedTemplate->id) {
                            $remainingTestIssues = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($remainingTestIssues) {
            $this->error('   FAIL: Test issues were still detected after repair.');
            $this->cleanup($pageWithMissingSlug, $pageWithMalformedTemplate);
            return 1;
        }

        $this->info('   All test issues have been resolved.');

        // 5. Clean up
        $this->info('');
        $this->info('5. Cleaning up dummy pages...');
        $this->cleanup($pageWithMissingSlug, $pageWithMalformedTemplate);

        $this->info('');
        $this->info('Database Health Check Test Completed Successfully!');
        return 0;
    }

    private function cleanup(Page $page1, Page $page2): void
    {
        $page1->forceDelete();
        $page2->forceDelete();
        $this->line('   Dummy pages have been deleted.');
    }
}
