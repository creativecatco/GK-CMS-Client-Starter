# Button Fields

## button (Single Button)

### Template Syntax

```blade
<div data-field="hero_cta" data-field-type="button">
    {!! $page->renderButton('hero_cta', ['text' => 'Get Started', 'link' => '/contact', 'style' => 'primary']) !!}
</div>
```

**CRITICAL:** Always use `renderButton()` — NEVER use `button()` in templates.
- `renderButton('key', defaults)` returns safe HTML — use with `{!! !!}`
- `button('key')` returns a PHP array — using `{{ $page->button('key') }}` in a template will crash the page with "Array to string conversion"

### Data Format

```json
{
    "text": "Get Started",
    "link": "/contact",
    "style": "primary",
    "custom_color": null,
    "custom_text_color": null
}
```

### Available Styles

| Style | Appearance |
|-------|-----------|
| `primary` | Filled button with primary color background |
| `secondary` | Outlined button with primary color border and text |

Buttons automatically use theme CSS variables for colors **unless** `custom_color` is set.

### Custom Color Overrides

When a user asks to change a button to a specific color (e.g., "make the button green" or "change the CTA to #FF5733"), use the `custom_color` and `custom_text_color` properties. These override the default theme-based styling for that specific button only.

| Property | Type | Description |
|----------|------|-------------|
| `custom_color` | CSS color string or `null` | Background color override. When set, this takes priority over the `style` preset. Use any valid CSS color: hex (`#00A86B`), rgb, or named color. Set to `null` to revert to the default theme style. |
| `custom_text_color` | CSS color string or `null` | Text color override. Optional. If omitted when `custom_color` is set, the system auto-detects contrast (light backgrounds get dark text, dark backgrounds get white text). |

**Example — changing a button to green:**

```json
{"hero_cta": {"text": "Get Started", "link": "/contact", "style": "primary", "custom_color": "#00A86B"}}
```

**Example — changing a button to red with white text:**

```json
{"hero_cta": {"text": "Get Started", "link": "/contact", "style": "primary", "custom_color": "#DC2626", "custom_text_color": "#FFFFFF"}}
```

**Example — reverting a button back to the default theme color:**

```json
{"hero_cta": {"text": "Get Started", "link": "/contact", "style": "primary", "custom_color": null, "custom_text_color": null}}
```

**IMPORTANT:** To change a button's color, you MUST use `update_page_fields` with the `custom_color` property. Do NOT attempt to change button colors by editing the template code or the page CSS. The `renderButton()` method handles all button styling.

### Updating via update_page_fields

```json
{"hero_cta": {"text": "Contact Us Today", "link": "/contact", "style": "primary"}}
```

You can update individual properties — the system merges with existing values.

## button_group (Multiple Buttons)

### Template Syntax

```blade
@php
    $buttons = $fields['hero_buttons'] ?? [
        ['text' => 'Get Started', 'link' => '/contact', 'style' => 'primary'],
        ['text' => 'Learn More', 'link' => '/about', 'style' => 'secondary'],
    ];
    if (is_string($buttons)) $buttons = json_decode($buttons, true) ?? [];
@endphp
<div data-field="hero_buttons" data-field-type="button_group" class="flex flex-wrap gap-4">
    @foreach($buttons as $index => $btn)
        <div data-repeater-item="{{ $index }}">
            {!! $page->renderButton("hero_buttons.{$index}", $btn) !!}
        </div>
    @endforeach
</div>
```

### Data Format

JSON array of button objects (each button supports `custom_color` and `custom_text_color`):

```json
[
    {"text": "Get Started", "link": "/contact", "style": "primary"},
    {"text": "Learn More", "link": "/about", "style": "secondary", "custom_color": "#10B981"}
]
```

### Updating via update_page_fields

```json
{
    "hero_buttons": [
        {"text": "Free Consultation", "link": "/contact", "style": "primary", "custom_color": "#7C3AED"},
        {"text": "Our Services", "link": "/services", "style": "secondary"}
    ]
}
```

## Common Button Patterns

### Hero with two buttons

```blade
<div class="flex flex-wrap gap-4 mt-8">
    <div data-field="hero_cta_primary" data-field-type="button">
        {!! $page->renderButton('hero_cta_primary', ['text' => 'Get Started', 'link' => '/contact', 'style' => 'primary']) !!}
    </div>
    <div data-field="hero_cta_secondary" data-field-type="button">
        {!! $page->renderButton('hero_cta_secondary', ['text' => 'Learn More', 'link' => '/about', 'style' => 'secondary']) !!}
    </div>
</div>
```

### CTA section button

```blade
<div class="mt-8 text-center" data-field="cta_button" data-field-type="button">
    {!! $page->renderButton('cta_button', ['text' => 'Contact Us', 'link' => '/contact', 'style' => 'primary']) !!}
</div>
```
