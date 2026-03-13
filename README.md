# GK CMS Client Starter

A Laravel client website starter project pre-configured with the **GK CMS Core** package by Creative Cat Co.

---

## Quick Start

### 1. Clone this repository

```bash
git clone https://github.com/creativecatco/GK-CMS-Client-Starter.git client-site
cd client-site
```

### 2. Install dependencies

```bash
composer install
npm install && npm run build
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials and CMS settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=client_cms
DB_USERNAME=root
DB_PASSWORD=

CMS_SITE_NAME="Client Website"
CMS_ADMIN_PATH=/admin
CMS_THEME=theme
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Create an admin user

```bash
php artisan tinker
```

```php
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->role = 'admin';
$user->save();
```

### 6. Link storage and serve

```bash
php artisan storage:link
php artisan serve
```

Visit `http://localhost:8000/admin` to access the CMS admin panel.

---

## Customizing the Theme

Theme views are located in `resources/views/theme/`:

```
resources/views/theme/
├── pages/
│   └── default.blade.php      # Default page template
├── blog/
│   ├── index.blade.php        # Blog listing
│   └── show.blade.php         # Single post
```

To create a page-specific template, add a file named after the page slug:

```
resources/views/theme/pages/about.blade.php    # For slug "about"
resources/views/theme/pages/contact.blade.php  # For slug "contact"
```

---

## Overriding Package Views

To override the base layout, header, or footer:

```bash
php artisan vendor:publish --tag=cms-views
```

This publishes views to `resources/views/vendor/cms-core/`.

---

## Publishing CMS Config

```bash
php artisan vendor:publish --tag=cms-config
```

---

## Deployment to SiteGround

1. Push your changes to this repository
2. SSH into your SiteGround server or use FTP
3. Upload the project files to `public_html/`
4. Set `public/` as the document root
5. Run `composer install --no-dev` on the server
6. Configure `.env` with production database credentials
7. Run `php artisan migrate --force`
8. Run `php artisan storage:link`

---

## Package Documentation

For full CMS Core documentation, see the [gk-cms-core repository](https://github.com/creativecatco/gk-cms-core).

---

## License

Proprietary — Creative Cat Co.
