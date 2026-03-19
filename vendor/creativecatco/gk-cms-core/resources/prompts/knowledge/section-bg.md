# section_bg Field Type

## Overview

`section_bg` is a complex field type for section backgrounds. It supports background colors, images, and overlays. Unlike `image` fields (which are simple strings), `section_bg` fields are **JSON objects**.

## How section_bg Rendering Works (CRITICAL)

The background is rendered by the `$page->sectionBgStyle('field_key')` method, which generates inline CSS from the JSON data. The template MUST include this method call in a `style` attribute:

```blade
<section data-field="hero_bg" data-field-type="section_bg"
         style="{{ $page->sectionBgStyle('hero_bg') }}">
```

**If the template does NOT have `sectionBgStyle()` in the style attribute, the background will NOT render.** The `data-field-type="section_bg"` attribute alone is NOT enough — it's only metadata for the visual editor.

## Overlay Rendering (CRITICAL)

The overlay is a **separate HTML element** inside the section. The `sectionBgStyle()` method only handles the background image/color — it does NOT create the overlay.

**The overlay requires BOTH:**
1. A `<div>` element in the template with the overlay CSS
2. The overlay settings in the field JSON data

### Correct Template Structure with Overlay

```blade
<section data-field="hero_bg" data-field-type="section_bg"
         style="{{ $page->sectionBgStyle('hero_bg') }}"
         class="relative">

    {{-- Overlay div — NEVER remove this --}}
    @php
        $heroBg = $page->field('hero_bg');
        $overlayStyle = '';
        if (is_array($heroBg) && isset($heroBg['overlay']['type'])) {
            if ($heroBg['overlay']['type'] === 'solid' && isset($heroBg['overlay']['solid'])) {
                $overlayStyle = 'background-color: ' . $heroBg['overlay']['solid'] . ';';
            } elseif ($heroBg['overlay']['type'] === 'gradient' && isset($heroBg['overlay']['gradient'])) {
                $overlayStyle = 'background: ' . $heroBg['overlay']['gradient'] . ';';
            }
        }
    @endphp
    @if($overlayStyle)
        <div class="absolute inset-0 z-0" style="{{ $overlayStyle }}"></div>
    @endif

    {{-- Content must be above the overlay --}}
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section content here --}}
    </div>
</section>
```

### CRITICAL RULES for Overlays

1. **NEVER remove the overlay `<div>` from a template.** If you remove it, the overlay will stop rendering even if the field data has overlay settings.
2. **To add an overlay:** You need BOTH the template `<div>` AND the field data `overlay` property.
3. **To change overlay opacity:** Update the field data `overlay.solid` value (e.g., `"rgba(0,0,0,0.6)"`). Do NOT modify the template.
4. **To remove an overlay:** Set `overlay.type` to `"none"` in the field data. Do NOT remove the template `<div>`.
5. **The section must have `class="relative"`** for the overlay positioning to work.
6. **Content must have `class="relative z-10"`** to appear above the overlay.

## Data Format

```json
{
  "color": "#1a1a2e",
  "colorType": "solid",
  "gradient": null,
  "image": "media/ai-generated/hero-banner.png",
  "mode": "cover",
  "overlay": {
    "type": "none"
  }
}
```

### Properties

| Property | Type | Values | Description |
|----------|------|--------|-------------|
| `color` | string/null | Hex color or null | Background color |
| `colorType` | string | `"solid"`, `"gradient"` | Color mode |
| `gradient` | object/null | Gradient config or null | Only used when colorType is "gradient" |
| `image` | string/null | Storage-relative path | Background image path (e.g., `"media/ai-generated/hero.png"`) |
| `mode` | string | `"cover"`, `"contain"`, `"repeat"`, `"fixed"` | How the image is displayed |
| `overlay` | object | `{type, solid?, gradient?}` | Overlay on top of the background |

### Mode Options

| Mode | Effect |
|------|--------|
| `cover` | Image fills the section, cropping if needed (most common) |
| `contain` | Image fits within the section without cropping |
| `repeat` | Image tiles/repeats |
| `fixed` | Parallax scrolling effect |

### Overlay Options

| overlay.type | Additional Properties | Effect |
|-------------|----------------------|--------|
| `"none"` | — | No overlay |
| `"solid"` | `overlay.solid`: CSS color | Solid color overlay, e.g., `"rgba(0,0,0,0.5)"` |
| `"gradient"` | `overlay.gradient`: gradient config | Gradient overlay |

## How to Update

### Changing the image only

1. Call `get_field_value` to get the current field value
2. Copy the existing JSON object
3. Change ONLY the `"image"` key
4. Pass the complete object to `update_page_fields`

```json
{"hero_bg": {"image": "media/ai-generated/new-hero.png", "mode": "cover", "color": "#1a1a2e", "colorType": "solid", "gradient": null, "overlay": {"type": "none"}}}
```

### Adding/changing an overlay

1. Call `get_field_value` to get the current value
2. Update the `overlay` property
3. Check if the template has an overlay `<div>` — if not, add one via `patch_page_template`

```json
{"hero_bg": {"image": "media/ai-generated/hero.png", "mode": "cover", "color": null, "colorType": "solid", "gradient": null, "overlay": {"type": "solid", "solid": "rgba(0,0,0,0.5)"}}}
```

### Color-only background (no image)

```json
{"section_bg": {"image": null, "mode": "cover", "color": "#1a1a2e", "colorType": "solid", "gradient": null, "overlay": {"type": "none"}}}
```

## Workflow for Adding an Overlay to an Existing Section

1. `get_field_value` — read the current section_bg value
2. `get_page_template` — check if the template has an overlay `<div>` for this section
3. If NO overlay div exists: `patch_page_template` to add one (see template structure above)
4. `update_page_fields` — set the overlay type and color in the field data
5. `render_page` — verify the overlay appears correctly

## Auto-Correction

If you accidentally pass a plain string (like `"media/ai-generated/hero.png"`) instead of a JSON object, the `update_page_fields` tool will auto-wrap it into a proper section_bg object. However, you should always use the correct JSON format to preserve existing color and overlay settings.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Passing a plain string instead of JSON object | Always use the full JSON object format |
| Forgetting to preserve existing values | Call `get_field_value` first, copy existing values |
| Using a full URL for the image | Use storage-relative path only (e.g., `"media/ai-generated/hero.png"`) |
| Removing the overlay `<div>` from the template | NEVER remove it — set `overlay.type` to `"none"` in field data instead |
| Adding overlay field data without the template `<div>` | The overlay needs BOTH the template element AND the field data |
| Trying to "upgrade" a text field to section_bg | Field types are defined by the template, not by field data. You must modify the template's `data-field-type` attribute |
| Hardcoding `style="background-image:..."` in the template | Use `style="{{ $page->sectionBgStyle('field_key') }}"` instead |
