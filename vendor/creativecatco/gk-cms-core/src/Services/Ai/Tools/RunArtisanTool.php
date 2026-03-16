<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Tool for running Laravel Artisan commands.
 *
 * Allows the AI to run migrations, clear caches, and perform
 * other maintenance tasks.
 *
 * Safety features:
 * - Only whitelisted commands are allowed
 * - Dangerous commands (like db:wipe, migrate:fresh) are blocked
 * - All commands are logged
 * - Output is captured and returned
 */
class RunArtisanTool extends AbstractTool
{
    /**
     * Commands that are allowed to be executed.
     */
    protected array $allowedCommands = [
        // Cache management
        'cache:clear',
        'config:clear',
        'config:cache',
        'view:clear',
        'view:cache',
        'route:clear',
        'route:cache',
        'optimize',
        'optimize:clear',

        // Database
        'migrate',
        'migrate:status',
        'db:show',

        // Maintenance
        'storage:link',
        'schedule:list',

        // Info
        'about',
        'env',
        'route:list',
    ];

    /**
     * Commands that are always blocked (even if they match a prefix).
     */
    protected array $blockedCommands = [
        'migrate:fresh',
        'migrate:reset',
        'migrate:rollback',
        'db:wipe',
        'db:seed',
        'key:generate',
        'down',
        'tinker',
    ];

    public function name(): string
    {
        return 'run_artisan';
    }

    public function description(): string
    {
        return 'Run a Laravel Artisan command. Useful for running migrations (after creating migration files), clearing caches, checking route lists, and other maintenance tasks. Only safe commands are allowed — destructive commands like migrate:fresh or db:wipe are blocked.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The Artisan command to run (e.g., "migrate", "cache:clear", "migrate:status", "route:list").',
                ],
                'arguments' => [
                    'type' => 'object',
                    'description' => 'Optional arguments and options for the command (e.g., {"--force": true} for migrate).',
                    'additionalProperties' => true,
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Brief explanation of why this command is needed.',
                ],
            ],
            'required' => ['command', 'reason'],
        ];
    }

    public function execute(array $params): array
    {
        $command = trim($params['command'] ?? '');
        $arguments = $params['arguments'] ?? [];
        $reason = $params['reason'] ?? '';

        if (empty($command)) {
            return $this->error('Command cannot be empty.');
        }

        // Check if command is blocked
        foreach ($this->blockedCommands as $blocked) {
            if (str_starts_with($command, $blocked)) {
                return $this->error("Command '{$command}' is blocked for safety. This command could cause data loss or system instability.");
            }
        }

        // Check if command is allowed
        $isAllowed = false;
        foreach ($this->allowedCommands as $allowed) {
            if ($command === $allowed || str_starts_with($command, $allowed . ' ')) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return $this->error(
                "Command '{$command}' is not in the allowed list. Allowed commands: " .
                implode(', ', $this->allowedCommands)
            );
        }

        Log::info('AI RunArtisan', [
            'command' => $command,
            'arguments' => $arguments,
            'reason' => $reason,
        ]);

        try {
            // For migrate, always add --force for production
            if ($command === 'migrate' && !isset($arguments['--force'])) {
                $arguments['--force'] = true;
            }

            $exitCode = Artisan::call($command, $arguments);
            $output = Artisan::output();

            return $this->success([
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => trim($output),
            ], $exitCode === 0 ? "Command '{$command}' completed successfully." : "Command '{$command}' finished with exit code {$exitCode}.");
        } catch (\Exception $e) {
            Log::error('AI RunArtisan error', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            return $this->error("Command failed: " . $e->getMessage());
        }
    }
}
