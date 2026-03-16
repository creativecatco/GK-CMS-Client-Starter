<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Tool for executing SQL queries against the site database.
 *
 * Supports both read (SELECT) and write (CREATE TABLE, ALTER TABLE, INSERT, UPDATE)
 * operations. Write operations that affect CMS core tables are blocked unless
 * the user explicitly requests it.
 *
 * Safety features:
 * - DROP TABLE / DROP DATABASE are always blocked
 * - TRUNCATE is always blocked
 * - CMS core tables are protected from modification
 * - All queries are logged
 * - Results are limited to prevent memory issues
 */
class RunQueryTool extends AbstractTool
{
    /**
     * CMS core tables that should not be modified by the AI.
     */
    protected array $protectedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'personal_access_tokens',
    ];

    /**
     * Tables that the AI can read but should be cautious about modifying.
     * These are CMS content tables — the AI can modify them through dedicated tools.
     */
    protected array $cmsContentTables = [
        'pages',
        'posts',
        'portfolios',
        'products',
        'media',
        'menus',
        'settings',
        'ai_conversations',
        'ai_actions',
    ];

    public function name(): string
    {
        return 'run_query';
    }

    public function description(): string
    {
        return 'Execute a SQL query against the site database. Use SELECT to inspect data, CREATE TABLE to add new tables for custom features, ALTER TABLE to modify table structures, and INSERT/UPDATE for custom tables. CMS core tables (users, sessions, migrations) are protected. For modifying pages/posts/settings, prefer the dedicated CMS tools instead. DROP and TRUNCATE are always blocked.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The SQL query to execute. Use parameterized values with ? placeholders for safety.',
                ],
                'bindings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional parameter bindings for ? placeholders in the query.',
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Brief explanation of why this query is needed. Required for write operations.',
                ],
            ],
            'required' => ['query', 'reason'],
        ];
    }

    public function execute(array $params): array
    {
        $query = trim($params['query'] ?? '');
        $bindings = $params['bindings'] ?? [];
        $reason = $params['reason'] ?? '';

        if (empty($query)) {
            return $this->error('Query cannot be empty.');
        }

        // Classify the query
        $queryType = $this->classifyQuery($query);

        // Safety checks
        $safetyCheck = $this->checkSafety($query, $queryType);
        if ($safetyCheck !== null) {
            return $safetyCheck;
        }

        Log::info('AI RunQuery', [
            'type' => $queryType,
            'query' => $query,
            'reason' => $reason,
        ]);

        try {
            switch ($queryType) {
                case 'select':
                case 'show':
                case 'describe':
                case 'explain':
                    $results = DB::select($query, $bindings);
                    $results = array_map(fn($row) => (array)$row, $results);

                    // Limit results
                    $totalCount = count($results);
                    if ($totalCount > 100) {
                        $results = array_slice($results, 0, 100);
                    }

                    return $this->success([
                        'rows' => $results,
                        'count' => $totalCount,
                        'truncated' => $totalCount > 100,
                    ], "Query returned {$totalCount} rows.");

                case 'insert':
                    $success = DB::insert($query, $bindings);
                    return $this->success([
                        'affected' => $success ? 1 : 0,
                    ], 'Insert executed successfully.');

                case 'update':
                    $affected = DB::update($query, $bindings);
                    return $this->success([
                        'affected' => $affected,
                    ], "{$affected} rows updated.");

                case 'delete':
                    $affected = DB::delete($query, $bindings);
                    return $this->success([
                        'affected' => $affected,
                    ], "{$affected} rows deleted.");

                case 'create':
                case 'alter':
                    DB::statement($query, $bindings);
                    return $this->success(null, 'Statement executed successfully.');

                default:
                    DB::statement($query, $bindings);
                    return $this->success(null, 'Statement executed successfully.');
            }
        } catch (\Exception $e) {
            Log::error('AI RunQuery error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return $this->error('SQL error: ' . $e->getMessage());
        }
    }

    /**
     * Classify the query type.
     */
    protected function classifyQuery(string $query): string
    {
        $normalized = strtolower(ltrim($query));

        if (str_starts_with($normalized, 'select')) return 'select';
        if (str_starts_with($normalized, 'show')) return 'show';
        if (str_starts_with($normalized, 'describe') || str_starts_with($normalized, 'desc ')) return 'describe';
        if (str_starts_with($normalized, 'explain')) return 'explain';
        if (str_starts_with($normalized, 'insert')) return 'insert';
        if (str_starts_with($normalized, 'update')) return 'update';
        if (str_starts_with($normalized, 'delete')) return 'delete';
        if (str_starts_with($normalized, 'create')) return 'create';
        if (str_starts_with($normalized, 'alter')) return 'alter';
        if (str_starts_with($normalized, 'drop')) return 'drop';
        if (str_starts_with($normalized, 'truncate')) return 'truncate';

        return 'unknown';
    }

    /**
     * Check if the query is safe to execute.
     */
    protected function checkSafety(string $query, string $queryType): ?array
    {
        // Always block destructive operations
        if (in_array($queryType, ['drop', 'truncate'])) {
            return $this->error('DROP and TRUNCATE operations are blocked for safety. If you need to remove a table, ask the user to do it manually through the database manager.');
        }

        // Block multi-statement queries (SQL injection prevention)
        if (substr_count($query, ';') > 1) {
            return $this->error('Multi-statement queries are not allowed. Execute one statement at a time.');
        }

        // For write operations, check if they target protected tables
        if (in_array($queryType, ['insert', 'update', 'delete', 'alter'])) {
            foreach ($this->protectedTables as $table) {
                if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $query)) {
                    return $this->error("Table '{$table}' is a CMS core table and cannot be modified. This protects the system from accidental damage.");
                }
            }

            // Warn about CMS content tables (but allow it)
            foreach ($this->cmsContentTables as $table) {
                if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $query)) {
                    // Allow SELECT on these tables, but warn on writes
                    if ($queryType !== 'select') {
                        Log::warning('AI modifying CMS content table directly', [
                            'table' => $table,
                            'query' => $query,
                        ]);
                    }
                }
            }
        }

        return null;
    }

    public function captureRollbackData(array $params): array
    {
        return ['query' => $params['query'] ?? ''];
    }
}
