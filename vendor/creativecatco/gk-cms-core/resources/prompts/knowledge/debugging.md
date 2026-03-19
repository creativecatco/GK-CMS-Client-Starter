# Debugging

## First Rule

**When something is broken, ALWAYS start with `read_error_log`.** It tells you exactly what went wrong, with file names, line numbers, and error messages. Do not guess.

## Diagnosis Sequence

1. **`read_error_log`** — See the actual error
2. **`render_page`** — See what the page looks like now
3. **`get_page_info`** — See the current template and field values
4. **Diagnose** — Explain to the user what happened
5. **Plan the fix** — Describe what you'll do BEFORE doing it
6. **Fix with minimal changes** — Smallest possible change
7. **Verify** — `render_page` to confirm the fix

## Common Error Patterns

### Blade Syntax Errors

**Symptoms:** 500 error, blank page, "Undefined variable" or "syntax error" in log

**Common causes:**
- Unmatched `@if` / `@endif`
- Unmatched `@foreach` / `@endforeach`
- Unmatched `@section` / `@endsection`
- Missing `@php` / `@endphp`
- Using `{{ }}` instead of `{!! !!}` for HTML content
- Typo in variable name

**Fix:** Use `get_page_info` to see the template, find the syntax error, fix with `update_page_template` making the minimal change.

### Field Value Format Errors

**Symptoms:** Page renders but content looks wrong, broken images, missing backgrounds

**Common causes:**
- `section_bg` field set to a string instead of JSON object
- `button` field set to a string instead of `{text, link, style}` object
- `repeater` field set to a string instead of JSON array
- Image path includes `/storage/` prefix (should be relative)
- Image path is a full URL (should be storage-relative)

**Fix:** Use `get_page_info` to see current field values, then `update_page_fields` with the correct format.

### Broken Header/Footer

**Symptoms:** EVERY page on the site shows errors or looks broken

**This is CRITICAL** — headers and footers are global components. A broken header/footer affects the entire site.

**Fix sequence:**
1. `read_error_log` — identify the error
2. `get_page_info` for the header/footer slug
3. Fix the template with the MINIMAL change needed
4. `render_page` on the homepage to verify
5. Check at least one other page too

### Missing Images

**Symptoms:** Broken image icons, empty spaces where images should be

**Common causes:**
- Image path is wrong (check with `get_page_info`)
- Image was never generated/uploaded
- Image path uses wrong format for the field type

**Fix:** Check the field value, generate/upload the image if needed, update with correct path format.

### Template Overwrote Field Values

**Symptoms:** Content reverted to defaults after a template update

**Cause:** `update_page_template` re-discovers fields. If field keys changed, old values are lost.

**Prevention:** Always preserve existing field keys when updating templates. Use `get_page_info` first.

**Fix:** Use `update_page_fields` to restore the content.

## What NOT to Do

| Wrong Approach | Right Approach |
|---------------|----------------|
| Rewrite the entire template to fix a small error | Find the specific line and fix it |
| Delete the page and recreate it | Fix what's broken |
| Make multiple rapid changes hoping one works | Diagnose first, then make ONE targeted fix |
| Claim it's fixed without verifying | Always `render_page` after fixing |
| Give up and tell user to fix manually | Try `read_error_log` first — it usually tells you exactly what's wrong |

## Using run_query for Advanced Debugging

When you need to inspect the database directly:

```sql
-- See all pages
SELECT id, title, slug, page_type, status FROM pages;

-- Check a specific page's fields
SELECT fields FROM pages WHERE slug = 'home';

-- Check settings
SELECT * FROM settings WHERE key LIKE 'theme_%';

-- Check media
SELECT * FROM media ORDER BY created_at DESC LIMIT 10;
```

Only use `run_query` for SELECT queries during debugging. Never use it for INSERT/UPDATE/DELETE — use the appropriate tools instead.
