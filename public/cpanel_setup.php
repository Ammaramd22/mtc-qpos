<?php
/**
 * QPOS browser setup for cPanel accounts with no Terminal.
 *
 * 1. In File Manager, edit SETUP_SECRET below. Use at least 16 random characters.
 * 2. Open https://YOURDOMAIN.com/cpanel_setup.php
 * 3. When the site works, delete this file.
 *
 * This page never runs migrate:fresh and never drops tables.
 */
declare(strict_types=1);

const SETUP_SECRET = 'CHANGE_ME_BEFORE_USE';

ini_set('display_errors', '0');
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

$basePath = qpos_base_path();
$weak = ['', 'CHANGE_ME_BEFORE_USE', '12345678', 'password', 'secret', 'qpos', 'qpossetup'];

if (in_array(SETUP_SECRET, $weak, true) || strlen(SETUP_SECRET) < 16) {
    http_response_code(403);
    render_page('Setup is locked', '<p>This page will not run until you set a private password.</p>
        <ol>
            <li>cPanel → <strong>File Manager</strong> → <code>public_html/cpanel_setup.php</code> → Edit.</li>
            <li>Change <code>SETUP_SECRET</code> to a private random string of at least 16 characters.</li>
            <li>Save, then reload this page and enter that password.</li>
            <li>Delete <code>public_html/cpanel_setup.php</code> after the shop is working.</li>
        </ol>');
    exit;
}

$sessionDir = $basePath.'/storage/framework/sessions';
if (! is_dir($sessionDir)) {
    @mkdir($sessionDir, 0775, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: cpanel_setup.php');
    exit;
}

$error = '';
$notice = '';

if (empty($_SESSION['qpos_setup_ok'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $given = (string) ($_POST['setup_password'] ?? '');
        if (hash_equals(SETUP_SECRET, $given)) {
            session_regenerate_id(true);
            $_SESSION['qpos_setup_ok'] = true;
            $_SESSION['qpos_setup_token'] = bin2hex(random_bytes(16));
            header('Location: cpanel_setup.php');
            exit;
        }
        $error = 'Wrong setup password.';
    }
    render_page('QPOS setup', '<p>Enter the <code>SETUP_SECRET</code> from <code>public_html/cpanel_setup.php</code>.</p>
        <form method="post">
            <label>Setup password<br><input type="password" name="setup_password" required autofocus></label>
            <button type="submit">Continue</button>
        </form>'.($error !== '' ? '<p class="bad">'.h($error).'</p>' : ''));
    exit;
}

if (empty($_SESSION['qpos_setup_token'])) {
    $_SESSION['qpos_setup_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['token'] ?? '');
    if (! hash_equals((string) $_SESSION['qpos_setup_token'], $token)) {
        $error = 'The form expired. Reload the page and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'save') {
                $notice = save_environment($basePath, $_POST);
            } elseif ($action === 'folders') {
                ensure_directories($basePath);
                $notice = 'Folders are in place. Set permissions to 775 on storage, bootstrap/cache, and uploads in this same public_html folder if a later step says they are not writable.';
            } elseif ($action === 'clear') {
                clear_laravel_cache($basePath);
                $notice = 'Cached config, views, and file cache were removed. The next page view rebuilds them.';
            } elseif ($action === 'migrate') {
                $notice = run_pending_migrations($basePath, $_POST);
            } elseif ($action === 'starter') {
                $notice = run_empty_database_setup($basePath, $_POST);
            } elseif ($action === 'close_registration') {
                $notice = set_registration($basePath, false);
            } else {
                $error = 'Unknown action.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$env = read_env($basePath.'/.env');
$checks = server_checks($basePath);
$db = null;
if (is_file($basePath.'/.env') && ($env['DB_DATABASE'] ?? '') !== '' && ($env['DB_USERNAME'] ?? '') !== '') {
    $db = inspect_database($env);
}
$hasBusiness = is_array($db) && ! empty($db['ok']) && in_array('business', $db['tables'], true);
$hasUsers = is_array($db) && ! empty($db['ok']) && in_array('users', $db['tables'], true);
$tableCount = is_array($db) && ! empty($db['ok']) ? count($db['tables']) : null;

$body = render_status($checks, $env, $db, $error, $notice);
$body .= render_form($env, $_SESSION['qpos_setup_token'], $hasBusiness, $hasUsers, $tableCount);
render_page('QPOS setup', $body);

function qpos_base_path(): string
{
    $candidates = [
        __DIR__,
        dirname(__DIR__).'/qpos',
        dirname(__DIR__),
    ];
    foreach ($candidates as $path) {
        if (is_file($path.'/artisan') && is_file($path.'/vendor/autoload.php')) {
            return $path;
        }
    }

    return dirname(__DIR__);
}

function qpos_web_root(string $basePath): string
{
    if (is_file($basePath.'/.qpos-web-root')) {
        return $basePath;
    }
    $sibling = dirname($basePath).'/public_html';
    if (is_file($sibling.'/.qpos-web-root')) {
        return $sibling;
    }

    return $basePath.'/public';
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>'.h($title).'</title><style>
        body{font-family:Segoe UI,Arial,sans-serif;background:#f4f6f8;color:#1f2933;margin:0}
        main{max-width:880px;margin:24px auto;background:#fff;border:1px solid #d9e2ec;border-radius:12px;padding:24px}
        h1{font-size:22px;margin:0 0 8px} code{background:#eef2f6;padding:1px 4px;border-radius:4px}
        label{display:block;margin:12px 0 4px;font-weight:600} input[type=text],input[type=password]{width:100%;max-width:520px;padding:8px;border:1px solid #bcccdc;border-radius:6px}
        button{margin:8px 8px 0 0;background:#00935f;color:#fff;border:0;border-radius:6px;padding:10px 14px;font-weight:600;cursor:pointer}
        button.secondary{background:#334e68} button.warn{background:#9b1c1c}
        .bad{background:#fff5f5;border:1px solid #feb2b2;padding:10px;border-radius:8px} .ok{background:#f0fff4;border:1px solid #9ae6b4;padding:10px;border-radius:8px}
        .box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin:12px 0}
        li{margin:4px 0}
        </style></head><body><main><h1>'.h($title).'</h1>'.$body.'</main></body></html>';
}

function render_status(array $checks, array $env, ?array $db, string $error, string $notice): string
{
    $html = '<p>No Terminal is required. This page does not drop tables.</p>';
    if ($error !== '') {
        $html .= '<p class="bad">'.h($error).'</p>';
    }
    if ($notice !== '') {
        $html .= '<p class="ok">'.nl2br(h($notice)).'</p>';
    }
    $html .= '<div class="box"><strong>Server</strong><ul>';
    foreach ($checks as $label => $ok) {
        $html .= '<li>'.h($label).': '.($ok ? 'yes' : 'NO').'</li>';
    }
    $html .= '</ul></div>';
    if ($env === []) {
        $html .= '<div class="box">No <code>.env</code> yet. Save the form below to create one.</div>';
    } else {
        $html .= '<div class="box"><code>.env</code> is present. APP_KEY '.(trim($env['APP_KEY'] ?? '') !== '' ? 'is set' : 'is empty and will be created when you save').'. APP_DEBUG='.h($env['APP_DEBUG'] ?? '').' APP_ENV='.h($env['APP_ENV'] ?? '').'</div>';
    }
    if ($db === null) {
        $html .= '<div class="box">Database not checked yet. Save the database fields first.</div>';
    } elseif (empty($db['ok'])) {
        $html .= '<div class="box bad">Database connection failed: '.h((string) $db['error']).'</div>';
    } else {
        $html .= '<div class="box">Connected. Tables: '.count($db['tables']).'. ';
        $html .= in_array('business', $db['tables'], true) ? 'Existing QPOS tables were found. Use pending migrations only.' : 'No business table. This database can be initialized.';
        $html .= '</div>';
    }
    return $html;
}

function render_form(array $env, string $token, bool $hasBusiness, bool $hasUsers, ?int $tableCount): string
{
    $url = $env['APP_URL'] ?? '';
    if ($url === '' || $url === 'https://YOURDOMAIN.com') {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme.'://'.($_SERVER['HTTP_HOST'] ?? 'YOURDOMAIN.com');
    }
    $fields = [
        'APP_NAME' => $env['APP_NAME'] ?? 'QPOS',
        'APP_URL' => $url,
        'APP_TIMEZONE' => $env['APP_TIMEZONE'] ?? 'Europe/London',
        'DB_HOST' => $env['DB_HOST'] ?? 'localhost',
        'DB_PORT' => $env['DB_PORT'] ?? '3306',
        'DB_DATABASE' => $env['DB_DATABASE'] ?? '',
        'DB_USERNAME' => $env['DB_USERNAME'] ?? '',
    ];
    $html = '<form method="post"><input type="hidden" name="token" value="'.h($token).'">';
    foreach ($fields as $name => $value) {
        $html .= '<label>'.h($name).'</label><input type="text" name="'.h($name).'" value="'.h($value).'">';
    }
    $html .= '<label>DB_PASSWORD</label><input type="password" name="DB_PASSWORD" value="" autocomplete="new-password">';
    $html .= '<p>Leave the password blank to keep the password already saved in <code>.env</code>.</p>';
    $html .= '<button type="submit" name="action" value="save">Save .env and APP_KEY</button>';
    $html .= '<button class="secondary" type="submit" name="action" value="folders">Create folders</button>';
    $html .= '<button class="secondary" type="submit" name="action" value="clear">Clear cache</button>';
    $html .= '</form>';

    $html .= '<form method="post" class="box"><input type="hidden" name="token" value="'.h($token).'">';
    $html .= '<strong>Pending migrations</strong><p>Applies new migrations only. Does not drop tables. Back up in phpMyAdmin first.</p>';
    $html .= '<label><input type="checkbox" name="backup_done" value="1"> I have a backup of this database.</label>';
    $html .= '<label>Type MIGRATE to continue</label><input type="text" name="confirm" value="">';
    $html .= '<button class="warn" type="submit" name="action" value="migrate">Apply pending migrations</button></form>';

    if (! $hasBusiness && ! $hasUsers && $tableCount === 0) {
        $html .= '<form method="post" class="box"><input type="hidden" name="token" value="'.h($token).'">';
        $html .= '<strong>Empty database</strong><p>Creates the tables, then loads currencies, permissions, and barcode layouts. It does not load sample products and it does not drop anything.</p>';
        $html .= '<label><input type="checkbox" name="backup_done" value="1"> I confirmed in phpMyAdmin that this database has no tables.</label>';
        $html .= '<label>Type CREATE to continue</label><input type="text" name="confirm" value="">';
        $html .= '<button type="submit" name="action" value="starter">Create tables and starter data</button></form>';
    } elseif ($tableCount === null) {
        $html .= '<div class="box">Save a working database connection before creating tables.</div>';
    } else {
        $html .= '<div class="box">Table creation is hidden because this database is not empty. Use pending migrations only.</div>';
    }

    $html .= '<form method="post"><input type="hidden" name="token" value="'.h($token).'">';
    $html .= '<button class="secondary" type="submit" name="action" value="close_registration">Turn off public registration</button></form>';
    $html .= '<p><a href="cpanel_setup.php?logout=1">Lock this page</a></p>';
    $html .= '<p class="bad">After you can log in, delete <code>public_html/cpanel_setup.php</code> and <code>public_html/cpanel_run_migrations.php</code> in File Manager.</p>';
    if (($env['ALLOW_REGISTRATION'] ?? '') === 'true') {
        $html .= '<p>Public registration is on. Create the first business at <a href="business/register">/business/register</a>, then click “Turn off public registration”.</p>';
    }
    return $html;
}

function server_checks(string $basePath): array
{
    $paths = [
        'storage writable' => is_writable($basePath.'/storage') || @mkdir($basePath.'/storage', 0775, true),
        'bootstrap/cache writable' => is_writable($basePath.'/bootstrap/cache'),
        'uploads writable' => is_writable(qpos_web_root($basePath).'/uploads') || @mkdir(qpos_web_root($basePath).'/uploads', 0775, true),
        'vendor/autoload.php' => is_file($basePath.'/vendor/autoload.php'),
    ];
    $checks = ['PHP 8.1 or 8.2' => PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80300];
    foreach (['openssl', 'pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'curl', 'zip', 'gd', 'bcmath', 'fileinfo', 'json', 'mysqli'] as $ext) {
        $checks[$ext] = extension_loaded($ext);
    }
    foreach ($paths as $label => $ok) {
        $checks[$label] = (bool) $ok;
    }
    return $checks;
}

function read_env(string $path): array
{
    if (! is_file($path)) {
        return [];
    }
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        if ($line === '' || str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $out[trim($key)] = trim($value, " \t\"'");
    }
    return $out;
}

function env_line(string $key, string $value): string
{
    $value = str_replace(["\r", "\n"], '', $value);
    return $key.'="'.str_replace('"', '\"', $value).'"';
}

function upsert_env(string $content, string $key, string $value): string
{
    $line = env_line($key, $value);
    if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $content)) {
        return (string) preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $content, 1);
    }
    return rtrim($content)."\n".$line."\n";
}

function save_environment(string $basePath, array $post): string
{
    $example = $basePath.'/.env.cpanel.example';
    $target = $basePath.'/.env';
    if (! is_file($example)) {
        throw new RuntimeException('.env.cpanel.example is missing.');
    }
    $current = is_file($target) ? (string) file_get_contents($target) : (string) file_get_contents($example);
    $existing = read_env($target);
    $map = [
        'APP_NAME' => trim((string) ($post['APP_NAME'] ?? 'QPOS')),
        'APP_TITLE' => trim((string) ($post['APP_NAME'] ?? 'QPOS')),
        'APP_URL' => rtrim(trim((string) ($post['APP_URL'] ?? '')), '/'),
        'APP_TIMEZONE' => trim((string) ($post['APP_TIMEZONE'] ?? 'Europe/London')),
        'DB_HOST' => trim((string) ($post['DB_HOST'] ?? 'localhost')),
        'DB_PORT' => trim((string) ($post['DB_PORT'] ?? '3306')),
        'DB_DATABASE' => trim((string) ($post['DB_DATABASE'] ?? '')),
        'DB_USERNAME' => trim((string) ($post['DB_USERNAME'] ?? '')),
    ];
    if ($map['APP_URL'] === '' || $map['DB_DATABASE'] === '' || $map['DB_USERNAME'] === '') {
        throw new RuntimeException('APP_URL, database name, and database username are required.');
    }
    $password = (string) ($post['DB_PASSWORD'] ?? '');
    if ($password === '') {
        $password = (string) ($existing['DB_PASSWORD'] ?? '');
    }
    if ($password === '') {
        throw new RuntimeException('Database password is required the first time you save.');
    }
    $map['DB_PASSWORD'] = $password;
    $map['APP_ENV'] = 'live';
    $map['APP_DEBUG'] = 'false';
    $map['SHOW_DEMO_SHOPS'] = 'false';
    $map['DB_CONNECTION'] = 'mysql';
    $map['CACHE_DRIVER'] = 'file';
    $map['SESSION_DRIVER'] = 'file';
    $map['QUEUE_CONNECTION'] = 'sync';
    $map['FILESYSTEM_DISK'] = 'local';
    $map['SESSION_SECURE_COOKIE'] = str_starts_with($map['APP_URL'], 'https://') ? 'true' : 'false';
    $key = trim((string) ($existing['APP_KEY'] ?? ''));
    if ($key === '') {
        $key = 'base64:'.base64_encode(random_bytes(32));
    }
    $map['APP_KEY'] = $key;
    if (! isset($existing['ALLOW_REGISTRATION'])) {
        $map['ALLOW_REGISTRATION'] = 'false';
    }
    foreach ($map as $name => $value) {
        $current = upsert_env($current, $name, $value);
    }
    if (@file_put_contents($target, $current) === false) {
        throw new RuntimeException('Could not write .env. Check that the folder is writable.');
    }
    @chmod($target, 0640);
    ensure_directories($basePath);
    $probe = inspect_database(read_env($target));
    if (empty($probe['ok'])) {
        throw new RuntimeException('The file was saved, but the database connection failed: '.$probe['error']);
    }
    return 'Saved .env. Database connection succeeded. Tables found: '.count($probe['tables']).'. APP_KEY is set.';
}

function set_registration(string $basePath, bool $allow): string
{
    $target = $basePath.'/.env';
    if (! is_file($target)) {
        throw new RuntimeException('Save .env before changing registration.');
    }
    $content = upsert_env((string) file_get_contents($target), 'ALLOW_REGISTRATION', $allow ? 'true' : 'false');
    if (@file_put_contents($target, $content) === false) {
        throw new RuntimeException('Could not update .env.');
    }
    clear_laravel_cache($basePath);
    return $allow ? 'Public registration is on.' : 'Public registration is off. Delete this setup file when you are finished.';
}

function ensure_directories(string $basePath): void
{
    $dirs = [
        'storage/app', 'storage/app/public', 'storage/app/pdf', 'storage/framework',
        'storage/framework/cache', 'storage/framework/cache/data', 'storage/framework/sessions',
        'storage/framework/views', 'storage/logs', 'bootstrap/cache',
    ];
    $webDirs = [
        'uploads', 'uploads/business_logos', 'uploads/documents', 'uploads/img',
        'uploads/invoice_logos', 'uploads/temp', 'uploads/login',
    ];
    foreach ($dirs as $dir) {
        $full = $basePath.'/'.$dir;
        if (! is_dir($full)) {
            @mkdir($full, 0775, true);
        }
    }
    $web = qpos_web_root($basePath);
    foreach ($webDirs as $dir) {
        $full = $web.'/'.$dir;
        if (! is_dir($full)) {
            @mkdir($full, 0775, true);
        }
    }
}

function clear_laravel_cache(string $basePath): void
{
    ensure_directories($basePath);
    $files = array_merge(
        glob($basePath.'/bootstrap/cache/*.php') ?: [],
        glob($basePath.'/storage/framework/views/*') ?: [],
        glob($basePath.'/storage/framework/cache/data/*') ?: []
    );
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            @unlink($file);
        }
    }
}

function inspect_database(array $env): array
{
    if (! extension_loaded('pdo_mysql')) {
        return ['ok' => false, 'error' => 'pdo_mysql is not enabled in the cPanel PHP version.', 'tables' => []];
    }
    $host = $env['DB_HOST'] ?? 'localhost';
    $port = $env['DB_PORT'] ?? '3306';
    $name = $env['DB_DATABASE'] ?? '';
    $user = $env['DB_USERNAME'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';
    try {
        $pdo = new PDO(
            'mysql:host='.$host.';port='.$port.';dbname='.$name.';charset=utf8mb4',
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        return ['ok' => true, 'error' => '', 'tables' => array_map('strval', $tables)];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Connection failed. Check the database name, username, password, and that the user has ALL PRIVILEGES.', 'tables' => []];
    }
}

function boot_laravel(string $basePath): void
{
    if (! is_file($basePath.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor/autoload.php is missing. Re-extract the ZIP.');
    }
    require_once $basePath.'/vendor/autoload.php';
    $app = require $basePath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

function confirm_or_stop(array $post, string $word): void
{
    if (($post['backup_done'] ?? '') !== '1' || strtoupper(trim((string) ($post['confirm'] ?? ''))) !== $word) {
        throw new RuntimeException('Tick the confirmation box and type '.$word.' before this action will run.');
    }
}

function run_pending_migrations(string $basePath, array $post): string
{
    confirm_or_stop($post, 'MIGRATE');
    if (! is_file($basePath.'/.env')) {
        throw new RuntimeException('Save .env first.');
    }
    boot_laravel($basePath);
    $code = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = trim(Illuminate\Support\Facades\Artisan::output());
    if ($code !== 0) {
        throw new RuntimeException('Migration failed. Restore the backup if any table changed. Details are in storage/logs/laravel.log.');
    }
    return "Pending migrations finished.\n".$output;
}

function run_empty_database_setup(string $basePath, array $post): string
{
    confirm_or_stop($post, 'CREATE');
    if (! is_file($basePath.'/.env')) {
        throw new RuntimeException('Save .env first.');
    }
    $env = read_env($basePath.'/.env');
    $db = inspect_database($env);
    if (empty($db['ok'])) {
        throw new RuntimeException($db['error']);
    }
    if (count($db['tables']) > 0) {
        throw new RuntimeException('This database already has tables. Creation was not run.');
    }
    boot_laravel($basePath);
    $code = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = trim(Illuminate\Support\Facades\Artisan::output());
    if ($code !== 0) {
        throw new RuntimeException('Creating tables failed. Details are in storage/logs/laravel.log.');
    }
    foreach ([
        'Database\\Seeders\\BarcodesTableSeeder',
        'Database\\Seeders\\PermissionsTableSeeder',
        'Database\\Seeders\\CurrenciesTableSeeder',
    ] as $class) {
        $seedCode = Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
        $output .= "\n".trim(Illuminate\Support\Facades\Artisan::output());
        if ($seedCode !== 0) {
            throw new RuntimeException('Tables were created, but starter data stopped at '.$class.'.');
        }
    }
    set_registration($basePath, true);
    return "Empty database is ready. Public registration is on so you can create the first business at /business/register. Turn it off after that.\n".$output;
}
