# GKeys CMS Security Guide

## Built-in Security Layers

GKeys CMS includes multiple security layers that replace the need for WordPress-style security plugins.

### 1. Security Headers Middleware (`SecurityHeaders`)

Applied to **all routes** (web and API). Adds the following headers:

| Header | Value | Protection |
|--------|-------|------------|
| `X-Frame-Options` | `SAMEORIGIN` | Prevents clickjacking |
| `X-Content-Type-Options` | `nosniff` | Prevents MIME sniffing |
| `X-XSS-Protection` | `1; mode=block` | Legacy XSS protection |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limits referrer leakage |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` | Restricts browser APIs |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Enforces HTTPS (production) |

### 2. Rate Limiting (`RateLimitApi`)

Applied to all CMS API routes:

- **Authenticated users**: 60 requests per minute per IP
- **Unauthenticated requests**: 30 requests per minute per IP
- Returns `429 Too Many Requests` with `retry_after` header when exceeded

### 3. Input Sanitization (`SanitizeInput`)

Applied to all authenticated write endpoints:

- Strips `<script>` tags and their content from text inputs
- Removes `javascript:` protocol from href/src attributes
- Removes `on*` event handlers (onclick, onload, onerror, etc.)
- **Exceptions**: Fields that intentionally contain HTML/JS (custom templates, CSS, head/body code) are NOT sanitized

### 4. File Upload Validation (`ValidateFileUpload`)

Applied to all file upload endpoints:

- **Blocked extensions**: `.php`, `.phtml`, `.sh`, `.exe`, `.htaccess`, `.env`, and 20+ more
- **Double extension detection**: Catches `file.php.jpg` attacks
- **MIME type validation**: Only allows images, videos, PDFs, and fonts
- **Image verification**: Uses `getimagesize()` to verify image files are real images
- **SVG script detection**: Scans SVG files for embedded `<script>` tags and event handlers
- **File size limit**: 20MB maximum per file

### 5. API Token Authentication (`ApiTokenAuth`)

For AI chatbot and external API access:

- Bearer token authentication via `Authorization: Bearer <token>` header
- Token stored in `.env` as `CMS_API_TOKEN`
- Separate `/api/cms/v1/` namespace for token-authenticated routes

### 6. Laravel Built-in Protections

GKeys CMS inherits all of Laravel's built-in security features:

- **CSRF Protection**: All form submissions require a valid CSRF token
- **SQL Injection Prevention**: Eloquent ORM uses parameterized queries
- **Mass Assignment Protection**: Models use `$fillable` whitelists
- **Password Hashing**: bcrypt by default
- **Session Security**: Encrypted, HTTP-only cookies
- **Blade XSS Protection**: `{{ }}` auto-escapes output; `{!! !!}` only used for admin-controlled content

## Recommended Additional Measures

### Server-Level (Hosting Provider)

1. **SSL/TLS Certificate**: Ensure HTTPS is enforced (Let's Encrypt or provider SSL)
2. **Firewall**: Configure server firewall (UFW, iptables, or provider WAF)
3. **PHP Configuration**:
   - `expose_php = Off`
   - `display_errors = Off` (production)
   - `allow_url_fopen = Off` (if not needed)
   - `disable_functions = exec,passthru,shell_exec,system,proc_open,popen`
4. **File Permissions**: Set proper ownership (`www-data`) and permissions (644 files, 755 directories)

### Application-Level

1. **Regular Updates**: Keep Laravel, Filament, and all packages updated
2. **Environment Security**: Never commit `.env` file; use strong `APP_KEY`
3. **Database Backups**: Set up automated daily backups
4. **Login Protection**: Consider adding two-factor authentication (2FA) via a Laravel package
5. **Content Security Policy**: For stricter environments, add a CSP header in `SecurityHeaders`
6. **Audit Logging**: Consider adding `spatie/laravel-activitylog` for admin action tracking

### Monitoring

1. **Error Tracking**: Use Sentry, Bugsnag, or Laravel Telescope in staging
2. **Uptime Monitoring**: Use UptimeRobot, Pingdom, or similar
3. **Log Review**: Regularly review `storage/logs/laravel.log` for suspicious activity
