## KubanOMS Laravel Rebuild

Start new Laravel 12 + Inertia + Vue 3 shell that mirrors the legacy Symfony CMS layout and data model.

### Filament resource generation (v5.x)
- Pages: `php artisan make:filament-resource "Cms/CmsPage" --panel=admin --generate` (title attribute: `title`)
- Menus: `php artisan make:filament-resource "Cms/CmsMenu" --panel=admin --generate` (title attribute: `title`)
- Menu items: `php artisan make:filament-resource "Cms/CmsMenuItem" --panel=admin --generate` (title attribute: `title`)
- Settings: `php artisan make:filament-resource "Cms/CmsSetting" --panel=admin --generate` (title attribute: `name`)
- File folders: `php artisan make:filament-resource "Cms/CmsFileFolder" --panel=admin --generate` (title attribute: `title`)
- Files: `php artisan make:filament-resource "Cms/CmsFile" --panel=admin --generate` (title attribute: `original_name`)
- After generation, adjust form/table schemas to match legacy fields (status/type selects, file uploads to `public/uploads`, relations, etc.).

### Stack & containers
- Docker services: `app` (php-fpm 8.3), `nginx` (port 8080), `mysql` (port 33060), `phpmyadmin` (port 8081).
- Legacy CSS/JS copied to `public/legacy` to preserve the current design.

### Quick start
1) Copy env and set keys  
`cp .env.example .env`  
`php artisan key:generate`

2) Up containers  
`docker compose up -d --build`

3) Install PHP/JS deps (requires network)  
`composer install`  
`npm install`

4) Migrate & seed demo data (структура создаётся явными миграциями; данные из legacy дампов не импортируются автоматически)  
`docker compose exec app php artisan migrate --seed`

5) Build/dev frontend  
`npm run dev` (or `npm run build`)

6) Access  
- Site: http://localhost:8080  
- phpMyAdmin: http://localhost:8081 (user/password from `.env`)
- Админка: `/admin` — нужно сгенерировать ресурсы Filament самостоятельно (мы убрали черновые заготовки). Учётка по умолчанию: `admin@example.com` / `password123`.

### Testing
- `php artisan test --testsuite=Feature --filter=PublicPagesTest`

### Notes
- Admin placeholder route `/admin` is protected by simple `role` field (admin/editor). Seeds create `admin@example.com` / `password123`.
- Spatie permissions + Breeze scaffolding still need installation when network access is available.
- Storage: run `php artisan storage:link` when enabling uploads.
- Filament зависит от установленной версии в composer.json; ресурсы нужно сгенерировать командами `make:filament-resource`.
 
  1. Страницы (cms_page):

  php artisan make:filament-resource "Cms/CmsPage" --panel=admin --generate

  - Title attribute: title
  - Разрешить редактирование: да (CRUD).

  2. Меню (cms_menu):

  php artisan make:filament-resource "Cms/CmsMenu" --panel=admin --generate

  - Title attribute: title
  - Разрешить редактирование: да.

  3. Пункты меню (cms_menu_item):

  php artisan make:filament-resource "Cms/CmsMenuItem" --panel=admin --generate

  - Title attribute: title
  - Разрешить редактирование: да.

  4. Настройки/блоки (cms_setting):

  php artisan make:filament-resource "Cms/CmsSetting" --panel=admin --generate

  - Title attribute: name
  - Разрешить редактирование: да.

  5. Папки файлов (cms_file_folder):

  php artisan make:filament-resource "Cms/CmsFileFolder" --panel=admin --generate

  - Title attribute: title
  6. Файлы (cms_file):

  php artisan make:filament-resource "Cms/CmsFile" --panel=admin --generate

  - Title attribute: original_name
  - Разрешить редактирование: да.




Для новостей используем ту же cms_page с page_of_type = 2 (тип “Новость”).

  Варианты:

  1. В админке Filament:

  - В CmsPage ресурсе добавь фильтр/скоуп по page_of_type = 2 и, если нужно, отдельную навигацию “Новости”.
  - В форме добавь select со значением по умолчанию 2 (“Новость”), поля: title, publication_date, content, path
    (обложка/файл), meta_*, статус page_status.

  2. Сидеры/импорт:

  - В DatabaseSeeder или отдельном сидере создавай записи в cms_page с page_of_type = 2, page_status = 3,
    publication_date заданной, url вида /news/slug.html, content с HTML и при необходимости path на обложку.
  - Если подтягиваешь данные из дампа, просто импортируй строки page_of_type=2 в новую БД (через временное подключение
    legacy).

  3. CLI/команда:

  - Можно добавить команду php artisan news:import которая копирует cms_page с page_of_type=2 из старой БД в новую.

  Коротко: в Filament — сделать фильтр/дефолт для page_of_type=2 и редактировать новости там; для автозаполнения —
  сидер или импорт командой, создающий записи в cms_page с нужным типом и статусом.
