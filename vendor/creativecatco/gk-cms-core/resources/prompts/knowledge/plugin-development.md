# Plugin Development

## When to Use Plugins

Use plugins when the user needs functionality that goes beyond standard CMS features:
- Custom forms or form handlers
- Third-party API integrations
- Custom routes or endpoints
- Custom database tables
- Custom business logic
- Scheduled tasks

**NEVER modify files in `vendor/creativecatco/`.** All custom code goes in `app/Plugins/`.

## Creating a Plugin

Use the `create_plugin` tool:

```
create_plugin(name: "ContactForm", description: "Custom contact form with email notifications")
```

This scaffolds:

```
app/Plugins/ContactForm/
    ContactFormPlugin.php    ← Main plugin class (service provider)
    routes.php               ← Custom routes
    Controllers/             ← Controller classes
    Models/                  ← Eloquent models
    views/                   ← Blade views
    migrations/              ← Database migrations
```

## Adding Custom Code

After scaffolding, use `write_file` to add your code:

### Custom Route

```php
// app/Plugins/ContactForm/routes.php
Route::post('/contact-submit', [ContactFormController::class, 'submit']);
```

### Custom Controller

```php
// app/Plugins/ContactForm/Controllers/ContactFormController.php
namespace App\Plugins\ContactForm\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContactFormController extends Controller
{
    public function submit(Request $request)
    {
        // Validate and process form
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Send email, save to database, etc.
        
        return redirect()->back()->with('success', 'Message sent!');
    }
}
```

### Custom Migration

Create via `run_artisan`:

```
run_artisan(command: "make:migration create_contact_submissions_table")
```

Then edit the migration file with `write_file`.

## Plugin Registration

The plugin's main class extends Laravel's ServiceProvider and is auto-discovered. It registers routes, views, and migrations automatically.

## Important Rules

1. **Never modify vendor files** — they get overwritten on updates
2. **Use the plugin namespace** — `App\Plugins\PluginName\`
3. **Follow Laravel conventions** — controllers, models, migrations, routes
4. **Test after creating** — use `render_page` or visit the route to verify
5. **Keep plugins focused** — one plugin per feature/integration
