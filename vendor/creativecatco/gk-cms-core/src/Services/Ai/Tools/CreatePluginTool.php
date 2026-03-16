<?php

namespace CreativeCatCo\GkCmsCore\Services\Ai\Tools;

use Illuminate\Support\Str;

class CreatePluginTool extends AbstractTool
{
    public function name(): string
    {
        return 'create_plugin';
    }

    public function description(): string
    {
        return 'Scaffold a new custom plugin for the CMS. Creates a proper Laravel plugin structure in app/Plugins/ with its own service provider, routes, views, migrations, and config. Use this instead of modifying CMS core files when the user needs custom functionality.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The plugin name in PascalCase (e.g., "ContactForm", "EventCalendar", "CustomGallery")',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'A brief description of what the plugin does',
                ],
                'features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of features to scaffold: "routes" (web routes), "api" (API routes), "views" (Blade views), "migration" (database migration), "model" (Eloquent model), "controller" (HTTP controller), "admin" (Filament admin page), "config" (config file), "middleware" (middleware class)',
                ],
            ],
            'required' => ['name', 'description'],
        ];
    }

    public function execute(array $params): array
    {
        $name = $params['name'] ?? '';
        $description = $params['description'] ?? '';
        $features = $params['features'] ?? ['routes', 'views', 'controller', 'config'];

        if (empty($name)) {
            return ['success' => false, 'error' => 'Plugin name is required'];
        }

        // Normalize the name
        $name = Str::studly($name);
        $slug = Str::kebab($name);
        $snake = Str::snake($name);

        $basePath = base_path("app/Plugins/{$name}");

        if (is_dir($basePath)) {
            return ['success' => false, 'error' => "Plugin '{$name}' already exists at app/Plugins/{$name}"];
        }

        try {
            $createdFiles = [];

            // Create directory structure
            $dirs = [
                $basePath,
                "{$basePath}/src",
            ];

            if (in_array('routes', $features) || in_array('api', $features)) {
                $dirs[] = "{$basePath}/routes";
            }
            if (in_array('views', $features)) {
                $dirs[] = "{$basePath}/resources/views";
            }
            if (in_array('migration', $features)) {
                $dirs[] = "{$basePath}/database/migrations";
            }
            if (in_array('model', $features)) {
                $dirs[] = "{$basePath}/src/Models";
            }
            if (in_array('controller', $features)) {
                $dirs[] = "{$basePath}/src/Http/Controllers";
            }
            if (in_array('middleware', $features)) {
                $dirs[] = "{$basePath}/src/Http/Middleware";
            }
            if (in_array('admin', $features)) {
                $dirs[] = "{$basePath}/src/Filament/Pages";
            }
            if (in_array('config', $features)) {
                $dirs[] = "{$basePath}/config";
            }

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // 1. Service Provider (always created)
            $providerContent = $this->generateServiceProvider($name, $slug, $description, $features);
            file_put_contents("{$basePath}/src/{$name}ServiceProvider.php", $providerContent);
            $createdFiles[] = "src/{$name}ServiceProvider.php";

            // 2. Routes
            if (in_array('routes', $features)) {
                $routesContent = $this->generateWebRoutes($name, $slug);
                file_put_contents("{$basePath}/routes/web.php", $routesContent);
                $createdFiles[] = "routes/web.php";
            }

            if (in_array('api', $features)) {
                $apiRoutesContent = $this->generateApiRoutes($name, $slug);
                file_put_contents("{$basePath}/routes/api.php", $apiRoutesContent);
                $createdFiles[] = "routes/api.php";
            }

            // 3. Controller
            if (in_array('controller', $features)) {
                $controllerContent = $this->generateController($name, $slug);
                file_put_contents("{$basePath}/src/Http/Controllers/{$name}Controller.php", $controllerContent);
                $createdFiles[] = "src/Http/Controllers/{$name}Controller.php";
            }

            // 4. Model
            if (in_array('model', $features)) {
                $modelContent = $this->generateModel($name, $snake);
                file_put_contents("{$basePath}/src/Models/{$name}.php", $modelContent);
                $createdFiles[] = "src/Models/{$name}.php";
            }

            // 5. Migration
            if (in_array('migration', $features)) {
                $migrationContent = $this->generateMigration($name, $snake);
                $migrationFile = date('Y_m_d_His') . "_create_{$snake}_table.php";
                file_put_contents("{$basePath}/database/migrations/{$migrationFile}", $migrationContent);
                $createdFiles[] = "database/migrations/{$migrationFile}";
            }

            // 6. Views
            if (in_array('views', $features)) {
                $viewContent = $this->generateView($name, $slug, $description);
                file_put_contents("{$basePath}/resources/views/index.blade.php", $viewContent);
                $createdFiles[] = "resources/views/index.blade.php";
            }

            // 7. Config
            if (in_array('config', $features)) {
                $configContent = $this->generateConfig($name, $slug);
                file_put_contents("{$basePath}/config/{$slug}.php", $configContent);
                $createdFiles[] = "config/{$slug}.php";
            }

            // 8. Middleware
            if (in_array('middleware', $features)) {
                $middlewareContent = $this->generateMiddleware($name);
                file_put_contents("{$basePath}/src/Http/Middleware/{$name}Middleware.php", $middlewareContent);
                $createdFiles[] = "src/Http/Middleware/{$name}Middleware.php";
            }

            // 9. Admin Page
            if (in_array('admin', $features)) {
                $adminContent = $this->generateAdminPage($name, $slug, $description);
                file_put_contents("{$basePath}/src/Filament/Pages/{$name}Page.php", $adminContent);
                $createdFiles[] = "src/Filament/Pages/{$name}Page.php";
            }

            // 10. README
            $readmeContent = $this->generateReadme($name, $slug, $description, $features, $createdFiles);
            file_put_contents("{$basePath}/README.md", $readmeContent);
            $createdFiles[] = "README.md";

            return [
                'success' => true,
                'plugin_name' => $name,
                'path' => "app/Plugins/{$name}",
                'files_created' => $createdFiles,
                'next_steps' => [
                    "1. Register the service provider in config/app.php: App\\Plugins\\{$name}\\src\\{$name}ServiceProvider::class",
                    '2. Run migrations if a migration was created: php artisan migrate',
                    '3. Customize the generated files to implement your feature',
                    "4. Access the plugin at: /{$slug} (if routes were created)",
                ],
                'message' => "Plugin '{$name}' scaffolded successfully at app/Plugins/{$name} with " . count($createdFiles) . " files.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to create plugin: ' . $e->getMessage()];
        }
    }

    protected function generateServiceProvider(string $name, string $slug, string $description, array $features): string
    {
        $loadRoutes = '';
        if (in_array('routes', $features)) {
            $loadRoutes .= "\n            \$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');";
        }
        if (in_array('api', $features)) {
            $loadRoutes .= "\n            \$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');";
        }

        $loadViews = '';
        if (in_array('views', $features)) {
            $loadViews = "\n            \$this->loadViewsFrom(__DIR__ . '/../resources/views', '{$slug}');";
        }

        $loadMigrations = '';
        if (in_array('migration', $features)) {
            $loadMigrations = "\n            \$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');";
        }

        $mergeConfig = '';
        if (in_array('config', $features)) {
            $mergeConfig = "\n            \$this->mergeConfigFrom(__DIR__ . '/../config/{$slug}.php', '{$slug}');";
        }

        return <<<PHP
<?php

namespace App\\Plugins\\{$name}\\src;

use Illuminate\\Support\\ServiceProvider;

/**
 * {$name} Plugin Service Provider
 *
 * {$description}
 */
class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {{$mergeConfig}
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {{$loadRoutes}{$loadViews}{$loadMigrations}
    }
}

PHP;
    }

    protected function generateWebRoutes(string $name, string $slug): string
    {
        return <<<PHP
<?php

use App\\Plugins\\{$name}\\src\\Http\\Controllers\\{$name}Controller;
use Illuminate\\Support\\Facades\\Route;

Route::prefix('{$slug}')->group(function () {
    Route::get('/', [{$name}Controller::class, 'index'])->name('{$slug}.index');
    Route::get('/{id}', [{$name}Controller::class, 'show'])->name('{$slug}.show');
});

PHP;
    }

    protected function generateApiRoutes(string $name, string $slug): string
    {
        return <<<PHP
<?php

use App\\Plugins\\{$name}\\src\\Http\\Controllers\\{$name}Controller;
use Illuminate\\Support\\Facades\\Route;

Route::prefix('api/{$slug}')->group(function () {
    Route::get('/', [{$name}Controller::class, 'apiIndex'])->name('api.{$slug}.index');
    Route::post('/', [{$name}Controller::class, 'apiStore'])->name('api.{$slug}.store');
});

PHP;
    }

    protected function generateController(string $name, string $slug): string
    {
        return <<<PHP
<?php

namespace App\\Plugins\\{$name}\\src\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Illuminate\\Routing\\Controller;

class {$name}Controller extends Controller
{
    /**
     * Display the main page.
     */
    public function index()
    {
        return view('{$slug}::index');
    }

    /**
     * Display a specific item.
     */
    public function show(\$id)
    {
        return view('{$slug}::index', ['id' => \$id]);
    }

    /**
     * API: List items.
     */
    public function apiIndex(Request \$request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => '{$name} API is working',
        ]);
    }

    /**
     * API: Create an item.
     */
    public function apiStore(Request \$request)
    {
        // TODO: Implement creation logic
        return response()->json([
            'success' => true,
            'message' => 'Item created',
        ]);
    }
}

PHP;
    }

    protected function generateModel(string $name, string $snake): string
    {
        return <<<PHP
<?php

namespace App\\Plugins\\{$name}\\src\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$name} extends Model
{
    protected \$table = '{$snake}s';

    protected \$fillable = [
        'name',
        'description',
        'status',
    ];

    protected \$casts = [
        'status' => 'boolean',
    ];
}

PHP;
    }

    protected function generateMigration(string $name, string $snake): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$snake}s', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->boolean('status')->default(true);
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$snake}s');
    }
};

PHP;
    }

    protected function generateView(string $name, string $slug, string $description): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLUGIN_NAME</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-12 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">PLUGIN_NAME</h1>
        <p class="text-gray-600 mb-8">PLUGIN_DESC</p>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500">Your plugin content goes here. Edit <code>resources/views/index.blade.php</code> to customize.</p>
        </div>
    </div>
</body>
</html>
BLADE;
    }

    protected function generateConfig(string $name, string $slug): string
    {
        return <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$name} Plugin Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env(strtoupper('{$slug}') . '_ENABLED', true),

    // Add your plugin configuration options here
];

PHP;
    }

    protected function generateMiddleware(string $name): string
    {
        return <<<PHP
<?php

namespace App\\Plugins\\{$name}\\src\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;

class {$name}Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request \$request, Closure \$next)
    {
        // Add your middleware logic here
        return \$next(\$request);
    }
}

PHP;
    }

    protected function generateAdminPage(string $name, string $slug, string $description): string
    {
        return <<<PHP
<?php

namespace App\\Plugins\\{$name}\\src\\Filament\\Pages;

use Filament\\Pages\\Page;

class {$name}Page extends Page
{
    protected static ?string \$navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string \$navigationLabel = '{$name}';
    protected static ?string \$title = '{$name}';
    protected static ?string \$navigationGroup = 'Plugins';
    protected static ?int \$navigationSort = 100;

    protected static string \$view = '{$slug}::admin';

    public function getDescription(): string
    {
        return '{$description}';
    }
}

PHP;
    }

    protected function generateReadme(string $name, string $slug, string $description, array $features, array $files): string
    {
        $fileList = implode("\n", array_map(fn($f) => "- `{$f}`", $files));

        return <<<MD
# {$name} Plugin

{$description}

## Installation

1. Register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    App\\Plugins\\{$name}\\src\\{$name}ServiceProvider::class,
],
```

2. Run migrations (if applicable):

```bash
php artisan migrate
```

## Files

{$fileList}

## Usage

Access the plugin at `/{$slug}` (if web routes are enabled).

## Customization

Edit the generated files to implement your custom functionality. The plugin structure follows Laravel conventions, so you can use all standard Laravel features.

MD;
    }
}
