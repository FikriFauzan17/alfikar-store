# Alfikar.com

Server-rendered Laravel storefront and admin application for catalog, articles, banners, and business contact settings.

## Requirements

- PHP 8.x, minimum 8.4.1 as required by `composer.json`, with the extensions required by Laravel and Composer's locked dependencies.
- A PDO driver for the selected database, plus GD with WebP support, EXIF, and Fileinfo for image uploads.
- Composer, Node.js/npm for asset builds, and a supported production database. MySQL 8.4.3 migrations and focused feature tests have been verified locally against a disposable schema; MariaDB and production-host compatibility have not been verified.
- HTTPS and a web server/reverse proxy configured to serve the application securely.

Run `composer check-platform-reqs --no-dev` and verify the selected PDO driver, `gd`, WebP, `exif`, and `fileinfo` in the PHP runtime used by both CLI and the web server before deployment. Laragon's PHP 8.3.33 does not meet the application requirement; select PHP 8.4.1 or newer for both runtimes. Image uploads allow up to 5 MiB per file and product galleries accept up to five files per request; configure `upload_max_filesize` to at least 6M, `post_max_size` to at least 30M, and provision adequate memory for image processing.

## Local setup

```sh
composer install
copy .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate
php artisan app:make-admin
php artisan storage:link
```

On Unix-like shells, use `cp .env.example .env` instead of `copy`. The administrator command prompts for credentials; do not create a shared/default production account. For local development, use `composer run dev`. Never run `migrate:fresh`, `migrate:refresh`, or `migrate:reset` against a database containing data you need.

## Production hosting and deployment

1. Deploy a reviewed release and set the web server document root to the release's `public/` directory. Do not expose the repository root, `.env`, `storage/`, `vendor/`, or source files over HTTP.
2. Configure secrets in the hosting secret manager (not in source control): `APP_KEY`, `APP_URL` with its HTTPS origin, database credentials, and any mail/storage credentials. Set `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_SECURE_COOKIE=true`. Keep `APP_KEY` stable and backed up securely; rotating it invalidates encrypted data and cookies.
3. Use HTTPS end-to-end where possible. If TLS terminates at a CDN/load balancer, configure Laravel's trusted proxies for that specific provider so secure requests and cookies are recognized. The application emits HSTS only for production requests Laravel recognizes as HTTPS.
4. Use a dedicated database identity with only the privileges required by the application and release migrations. Configure the cache/session store for the deployment topology; use shared stores when running multiple application instances.
5. Install locked production dependencies and build static assets:

    ```sh
    composer install --no-dev --prefer-dist --optimize-autoloader
    npm ci
    npm run build
    php artisan migrate --force
    php artisan storage:link
    php artisan optimize
    ```

    Back up the target database and public uploads before applying migrations. Check `php artisan migrate:status --no-ansi` against the target database; do not infer the target status from a local database. Apply pending migrations only after confirming the backup and selected database.

6. Run `php artisan app:make-admin` once through a protected administrative shell if no administrator exists. Protect shell access and the resulting credentials; do not seed a default user.
7. Give the web process write access only where needed, typically `storage/` and `bootstrap/cache/`. Use least-privilege ownership/modes; do not use `chmod -R 777`. Keep `storage/app/private/` inaccessible from the web. Product, article, and banner images are processed into public WebP variants and intentionally served via `public/storage`; do not store private documents there.
8. Confirm HTTPS redirects, secure session cookies, admin access, backups, the health endpoint, and the built CSS/JS on the real host before opening traffic. Monitor application, web-server, database, and CDN logs.

### Manual cPanel / shared hosting upload

Use this procedure when the host provides cPanel, SSH/Terminal, MySQL, and a configurable domain document root. Replace every path and credential placeholder with values shown by the host. If the host cannot set the document root to the application's `public/` directory or does not support the required PHP version/extensions, do not expose the project root; ask the host for a compatible Laravel setup.

1. In cPanel, create a MySQL database and a dedicated database user. Grant that user access only to this database. Record the database name, username, password, and database host exactly as cPanel displays them; the host is not always `localhost`.
2. In **Domains**, set the site's document root to `/home/CPANEL_USER/alfikar/public`. Upload/extract the project into `/home/CPANEL_USER/alfikar`, outside `public_html` if that directory is the web root. Never put `.env`, `vendor/`, `storage/`, or the application root inside a public document root.
3. Upload the source including `composer.json` and `composer.lock`, but not the local `.env`, local SQLite database, `node_modules/`, or local logs/cache. The ignored `vendor/`, `public/build/`, and `public/storage` paths are not included by Git: install/build/link them below. If existing articles/products have uploaded images, copy their `storage/app/public/` files separately without overwriting unrelated host files.
4. In cPanel's PHP selector/MultiPHP Manager, select PHP 8.4.1 or newer for the domain. Enable and verify GD with WebP, EXIF, Fileinfo, PDO, and PDO MySQL for both the web PHP runtime and CLI. Configure `upload_max_filesize=6M`, `post_max_size=30M`, and `memory_limit` of at least `256M`. Confirm the CLI executable uses the same PHP version as the domain.
5. Open cPanel Terminal/SSH and run these commands from the application root. Use the host's PHP/Composer/Node executable paths if they are not on `PATH`:

    ```sh
    cd /home/CPANEL_USER/alfikar
    composer install --no-dev --prefer-dist --optimize-autoloader
    npm ci
    npm run build
    ```

    If the host has no Node.js, run `npm ci` and `npm run build` on a trusted local build machine, then upload the generated `public/build/` directory. Do not upload `node_modules/`.

6. Create `/home/CPANEL_USER/alfikar/.env` on the server from `.env.example` or the cPanel editor. Set at least the following production values and use cPanel's actual database values:

    ```dotenv
    APP_NAME="Toko Buku Alfikar"
    APP_ENV=production
    APP_KEY=
    APP_DEBUG=false
    APP_URL=https://your-domain.example
    SESSION_SECURE_COOKIE=true
    DB_CONNECTION=mysql
    DB_HOST=HOST_FROM_CPANEL
    DB_PORT=3306
    DB_DATABASE=CPANEL_DATABASE
    DB_USERNAME=CPANEL_DATABASE_USER
    DB_PASSWORD=SET_PRIVATELY
    SESSION_DRIVER=database
    CACHE_STORE=database
    QUEUE_CONNECTION=database
    PRODUCT_IMAGE_DISK=public
    BACKUP_PATH=/home/CPANEL_USER/private/alfikar-backups
    ```

    Keep `.env` outside the document root and restrict it to the application account. Do not upload the development `.env` or reuse a local `APP_KEY`. Leave `APP_KEY` blank only for the first setup, then generate it once below and keep it stable.

7. Create the private backup directory outside `public/`, then run the following from the application root. Review the reported target connection and migration status before applying schema changes. Back up an existing target database first; on a new empty database, the migrations create the application tables.

    ```sh
    mkdir -p /home/CPANEL_USER/private/alfikar-backups
    php artisan key:generate --force
    php artisan migrate:status --no-ansi
    php artisan migrate --force
    php artisan storage:link
    php artisan app:make-admin
    php artisan optimize
    ```

    Run `app:make-admin` in a protected interactive SSH terminal and enter a unique administrator password. There is intentionally no default account. If symlinks are disabled by the host, ask the host to enable Laravel's `public/storage` link or provide an equivalent secure static-file mapping; do not make uploaded files directly executable.

8. Ensure the PHP/web account can write to `storage/` and `bootstrap/cache/` using the host's ownership controls. For a single-account cPanel setup, `chmod -R u+rwX storage bootstrap/cache` is a starting point if the application account owns the files. Do not use `777`.
9. In **Cron Jobs**, add the Laravel scheduler every minute. Replace the PHP path with the host's CLI binary that matches the selected PHP version:

    ```cron
    * * * * * cd /home/CPANEL_USER/alfikar && /PATH/TO/PHP artisan schedule:run >> /dev/null 2>&1
    ```

    The scheduler runs backups daily at 02:00 in the configured application timezone and prunes metrics. Keep `BACKUP_PATH` private and outside the document root, then verify a completed backup and its manifest; configuring a cron entry alone does not prove backups work.

10. Visit `https://your-domain.example/up` and confirm HTTP 200, then verify the home page, a published article, product images, CSS/JS assets, `/sitemap.xml`, admin login, and a test image upload. Confirm the admin session cookie is Secure over HTTPS. Check Laravel and web-server logs for errors before sharing the domain.

This checklist assumes cPanel Terminal/SSH is available. Exact PHP binary paths, document-root controls, symlink policy, MySQL hostname, cron UI, and permissions differ by provider; the hosting account's control panel/support documentation is authoritative for those values.

Deploy immutable releases where possible. Avoid running Composer/npm installs or build tools as the web-server user. If a release fails, use the host's rollback process and restore a matching database/files backup only after assessing migration compatibility. `php artisan optimize:clear` clears generated caches when correcting stale deployment configuration; rebuild caches after configuration changes.

## Backups and restore

The application provides `php artisan backup:run`, scheduled daily at 02:00 in `config('app.timezone')` (currently UTC). This only registers the task with Laravel; it does **not** run automatically until Windows Task Scheduler, a hosting cron, or the provider scheduler is configured. `withoutOverlapping` and a process lock prevent duplicate runs; multi-instance hosting also uses `onOneServer` and therefore needs a shared atomic cache.

Each completed snapshot contains a database file/dump, a copy of every file on the configured image disk, and `manifest.json`. `backup-history.jsonl` records only timestamp, status, snapshot id, and payload size. Failed and skipped runs are recorded without exception details or credentials. Completed snapshots older than `BACKUP_RETENTION_DAYS` (default 30) are pruned; incomplete temporary snapshots older than one day are also removed on a later successful run.

Set `BACKUP_PATH` to an absolute, private directory outside the project, Git, and web document root. The local default is a sibling directory named `<project>-backups`; set a separate path in local and hosting environments. `BACKUP_IMAGE_DISK` defaults to `PRODUCT_IMAGE_DISK` (or `public`). Linux restricts the backup root to mode `0700`; Windows applies an NTFS ACL for the account running PHP. On first use the directory must be empty and dedicated; new snapshots inherit its restricted permissions. Run the scheduled task as that same dedicated account and verify it can read the DB and image disk. The dump does not contain `.env`, DB credentials, or `APP_KEY`; keep `APP_KEY` in a separate protected secret store. Backups are not encrypted by the application: use an encrypted volume and encrypted off-site storage for sensitive production data. Do not put `storage/app/private/` into these snapshots unless a separate requirement is approved.

### Local scheduler on Windows/Laragon

1. Choose the Laragon PHP CLI executable and set `BACKUP_PATH` in the local `.env` to a private NTFS directory outside the project and web root. Do not copy local database credentials into hosting configuration.
2. In Windows Task Scheduler, create a task running as the dedicated account that owns the backup directory. Set a trigger to repeat every 1 minute indefinitely.
3. Set **Program/script** to the selected `php.exe`, **Add arguments** to `artisan schedule:run`, and **Start in** to the application root. Do not invoke the backup command directly on every minute; Laravel decides when its daily task is due.
4. After the next scheduled 02:00 application-time run, check `backup-history.jsonl`, the new snapshot manifest, and available disk space. Configuring the task does not prove a successful run.

### Hosting scheduler

Set a different absolute `BACKUP_PATH` through the host's secret/configuration mechanism, create the directory for the application service account, and restrict access to that account and approved backup operators. Configure the provider's Laravel scheduler or a single cron entry:

```cron
* * * * * cd /path/to/release && php artisan schedule:run >> /dev/null 2>&1
```

For multiple scheduler nodes, use the same shared atomic cache store for `onOneServer`, and ensure the backup path is shared and private if the command could execute on different nodes. Replicate completed backups off-site through an encrypted provider-managed mechanism; retention on the application host is not an off-site copy.

The SQLite adapter creates a consistent file-backed snapshot with `VACUUM INTO`. MySQL/MariaDB is streamed through PDO into a SQL file; table definitions and rows are included, but views, triggers, stored routines, users, and grants are not. Other database drivers and SQLite `:memory:` are rejected. The database is snapshotted before image files are copied, so avoid concurrent image uploads/deletions during the run or use provider-level coordinated snapshots when strict cross-store consistency is required.

### Restore drill

Never point restore steps at the live application database or image directory during a drill. Select a completed snapshot, copy its database artifact to a separate recovery database/path, copy `images/` to a separate directory, and verify a known database row plus the expected image count and file contents before treating it as recoverable. SQLite restores use the copied `database.sqlite`; MySQL/MariaDB restores require an empty isolated database of a compatible version. Supply credentials interactively, not in a command argument:

```sh
mysql --user=alfikar_restore --password --database=alfikar_restore < /private-backups/<snapshot>/database.sql
```

On Windows, run the equivalent `mysql.exe --user=... --password --database=... < "...\database.sql"` from Laragon Terminal or `cmd.exe`, targeting a separately created recovery database. In both cases, the SQL artifact intentionally does not drop existing tables, so restore to an empty database. Copy the snapshot's `images/` contents into the isolated image root while preserving relative paths. Do not copy `.env` or expose the snapshot over HTTP.

For an actual recovery, first rehearse in isolation, stop writes, preserve current database/files as a rollback point, restore compatible artifacts, verify permissions, application health, and `php artisan migrate:status`, then reopen traffic. There is no web restore button or production restore command. A local drill does not verify MySQL/hosting restore behavior; repeat the drill against an isolated environment matching the target host.

## Routine maintenance

- Review and apply Laravel/PHP/Node and application security updates; run `composer audit` and `npm audit` during release review.
- Review admin accounts, access logs, failed logins, storage/database capacity, backup success, certificate renewal, and monitoring alerts.
- Check `php artisan migrate:status` and application logs after releases. Run scheduled queue/scheduler workers only if future features require them; article publication currently evaluates schedule timestamps on requests.
- Re-test login, admin authorization, image upload limits, public draft visibility, contact/WhatsApp links, sitemap, and restore procedures after material changes.
- Keep `APP_DEBUG=false`, protect database and backup credentials, and avoid logging session tokens, passwords, or other secrets.

## Edge protection and operational risks

Use a reputable CDN/WAF or hosting edge firewall for managed DDoS absorption, bot controls, request-rate limits, and request-size limits. Restrict direct origin access to the edge where practical, preserve accurate client IP/proxy configuration, and rate-limit sensitive endpoints (especially login) at both application and edge layers. Maintain provider-side network protection, alerting, incident contacts, and an origin recovery plan.

An application WAF or these response headers cannot prevent every attack or availability incident. They do not replace secure deployment, patching, access controls, backups, monitoring, provider DDoS services, or incident response. The current Content Security Policy permits inline styles to accommodate existing presentation; it disallows inline scripts and frames. Review browser console/network behavior after CSP changes and consider removing inline style attributes before tightening that policy.

## Verification

Run focused tests with the repository's Pest suite, for example:

```sh
php artisan test --compact
npm run build
```

The SQLite suite and focused MySQL 8.4.3 migration/catalog/statistics tests have passed locally. This does not verify MariaDB, the selected hosting provider, production proxy/TLS configuration, load characteristics, scheduled backups, or a real-host restore. Verify those items in the target environment before opening traffic; do not infer production readiness from local tests alone.
