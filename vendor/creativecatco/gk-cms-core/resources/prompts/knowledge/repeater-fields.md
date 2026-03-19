# Repeater Fields

## Overview

Repeater fields hold a JSON array of objects, where each object has the same set of sub-fields. Use them for any list of similar items: services, team members, features, testimonials, FAQ items, pricing plans, etc.

## Data Format

```json
[
    {"icon": "shield", "title": "Security", "desc": "Enterprise-grade security for your data."},
    {"icon": "zap", "title": "Speed", "desc": "Lightning-fast performance."},
    {"icon": "heart", "title": "Support", "desc": "24/7 dedicated support team."}
]
```

## Template Syntax

```blade
@php
    $items = $fields['services_items'] ?? [
        ['icon' => 'star', 'title' => 'Service', 'desc' => 'Description'],
    ];
    if (is_string($items)) $items = json_decode($items, true) ?? [];
@endphp
<div data-field="services_items" data-field-type="repeater" class="grid md:grid-cols-3 gap-8">
    @foreach($items as $index => $item)
        <div data-repeater-item="{{ $index }}" class="p-6 rounded-lg">
            <span data-field="services_items.{{ $index }}.icon" data-field-type="icon"
                  data-icon-name="{{ $item['icon'] ?? 'star' }}" class="w-12 h-12"
                  style="color: var(--color-primary);"></span>
            <h3 data-field="services_items.{{ $index }}.title" data-field-type="text"
                class="mt-4 text-xl font-semibold" style="color: var(--color-text);">
                {{ $item['title'] ?? 'Title' }}
            </h3>
            <p data-field="services_items.{{ $index }}.desc" data-field-type="textarea"
               class="mt-2" style="color: var(--color-text); opacity: 0.7;">
                {{ $item['desc'] ?? 'Description' }}
            </p>
        </div>
    @endforeach
</div>
```

## Critical Rules

1. **Parent element** MUST have `data-field="key"` and `data-field-type="repeater"`
2. **Each item** MUST have `data-repeater-item="{{ $index }}"`
3. **Sub-fields** use **dot notation**: `data-field="key.{{ $index }}.subfield"`
4. **Always parse JSON**: `if (is_string($items)) $items = json_decode($items, true) ?? [];`
5. **Always provide defaults** for both the array and each sub-field value

## Updating via update_page_fields

Pass the complete array:

```json
{
    "services_items": [
        {"icon": "shield", "title": "Security", "desc": "Enterprise-grade security."},
        {"icon": "zap", "title": "Performance", "desc": "Blazing fast speeds."},
        {"icon": "users", "title": "Collaboration", "desc": "Work together seamlessly."}
    ]
}
```

To add an item, include the full array with the new item appended. To remove an item, include the full array without it. To reorder, change the array order.

## Common Repeater Patterns

### Team Members

Sub-fields: `name` (text), `role` (text), `bio` (textarea), `image` (image)

### FAQ Accordion

Sub-fields: `question` (text), `answer` (textarea)

```blade
@foreach($items as $index => $item)
    <div data-repeater-item="{{ $index }}" x-data="{open: false}" class="border-b">
        <button @click="open = !open" class="w-full text-left py-4 flex justify-between items-center">
            <span data-field="faq_items.{{ $index }}.question" data-field-type="text" class="font-semibold">
                {{ $item['question'] ?? 'Question?' }}
            </span>
            <span x-text="open ? '−' : '+'" class="text-xl"></span>
        </button>
        <div x-show="open" x-collapse>
            <p data-field="faq_items.{{ $index }}.answer" data-field-type="textarea" class="pb-4">
                {{ $item['answer'] ?? 'Answer goes here.' }}
            </p>
        </div>
    </div>
@endforeach
```

### Pricing Plans

Sub-fields: `name` (text), `price` (text), `period` (text), `features` (textarea), `cta_link` (text), `highlighted` (text)

### Statistics/Counters

Sub-fields: `number` (text), `label` (text)

```blade
<div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
    @foreach($items as $index => $item)
        <div data-repeater-item="{{ $index }}">
            <div data-field="stats_items.{{ $index }}.number" data-field-type="text"
                 class="text-4xl font-bold" style="color: var(--color-primary);">
                {{ $item['number'] ?? '100+' }}
            </div>
            <div data-field="stats_items.{{ $index }}.label" data-field-type="text"
                 class="mt-2 text-sm uppercase tracking-wide" style="color: var(--color-text); opacity: 0.6;">
                {{ $item['label'] ?? 'Metric' }}
            </div>
        </div>
    @endforeach
</div>
```
