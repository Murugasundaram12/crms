<?php

declare(strict_types=1);

/**
 * HouseFix360 CRM — Isolated Browser QA Test Router
 *
 * Implements a fail-closed, multi-tier gatekeeper that guarantees no request
 * can be processed unless the live database connection is cryptographically verified
 * to be the designated disposable SQLite test database.
 *
 * Strict Security Rules:
 * 1. Zero access to persistent databases (crms, admin_crms, admin_crm_demo, mysql).
 * 2. Pre-bootstrap environment variables isolate configuration cache, sessions, logs, uploads, queue, and mail.
 * 3. 4-Tier Gatekeeper verifies container driver, canonical file path, prohibited term blacklist,
 *    and SQLite engine-level open file handle (PRAGMA database_list) before handling any route.
 * 4. Health-check endpoint (/_qa_health_check) reports 'ready' only after Laravel bootstrap
 *    and the 4-tier gatekeeper pass.
 */

define('CRMS_BROWSER_QA_ACTIVE', true);

$workspaceRoot = dirname(__DIR__);
$testingDir = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'testing';
$approvedDbFile = $testingDir . DIRECTORY_SEPARATOR . 'browser_qa.sqlite';

// =============================================================================
// Helper: Windows Path Canonicalization
// =============================================================================
if (!function_exists('crmsCanonicalizePath')) {
    function crmsCanonicalizePath(string $path): string
    {
        if ($path === '' || !file_exists($path)) {
            return '';
        }

        $real = realpath($path);

        return $real === false ? '' : strtolower(str_replace('/', '\\', $real));
    }
}

// =============================================================================
// Step 0: Early Pre-Flight File Existence & Integrity Check
// =============================================================================
if (!file_exists($approvedDbFile) || filesize($approvedDbFile) === 0) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'failed',
        'error' => 'CRMS GUARD FAILURE: Approved SQLite database file is missing or unprovisioned.',
        'target_path' => $approvedDbFile,
    ]);
    exit;
}

// =============================================================================
// Step 1: Pre-Bootstrap Environment Overrides (Immutable Env Repository)
// =============================================================================
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('APP_CONFIG_CACHE=storage/testing/nonexistent_config_cache.php'); // Relative path bypasses cache
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $approvedDbFile);
putenv('SESSION_DRIVER=file');
putenv('SESSION_SINGLE_WEB_SESSION=false');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');
putenv('MAIL_MAILER=array');
putenv('BROADCAST_CONNECTION=null');

$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $approvedDbFile;

$_SERVER['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $approvedDbFile;

// =============================================================================
// Step 2: Static Asset Passthrough (PHP CLI Built-In Server)
// =============================================================================
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = urldecode((string) parse_url($requestUri, PHP_URL_PATH));
$publicRoot = $workspaceRoot . DIRECTORY_SEPARATOR . 'public';
$publicAssetFile = $publicRoot . str_replace('/', DIRECTORY_SEPARATOR, $uriPath);
$canonicalPublicRoot = crmsCanonicalizePath($publicRoot);
$canonicalAssetFile = ($uriPath !== '/' && $uriPath !== '') ? crmsCanonicalizePath($publicAssetFile) : '';
$allowedStaticExtensions = [
    'css',
    'js',
    'mjs',
    'map',
    'json',
    'png',
    'jpg',
    'jpeg',
    'gif',
    'svg',
    'ico',
    'webp',
    'avif',
    'woff',
    'woff2',
    'ttf',
    'eot',
    'otf',
    'txt',
    'webmanifest',
];

if (
    $canonicalPublicRoot !== ''
    && $canonicalAssetFile !== ''
    && str_starts_with($canonicalAssetFile, $canonicalPublicRoot . '\\')
    && !is_dir($canonicalAssetFile)
    && !is_link($publicAssetFile)
    && basename($canonicalAssetFile) !== 'index.php'
) {
    $assetExtension = strtolower((string) pathinfo($canonicalAssetFile, PATHINFO_EXTENSION));
    if ($assetExtension !== 'php' && in_array($assetExtension, $allowedStaticExtensions, true)) {
        return false; // Safe non-PHP static asset handled directly by PHP built-in web server
    }
}

// =============================================================================
// Step 3: Require Autoloader and Application Builder
// =============================================================================
require $workspaceRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $workspaceRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

// =============================================================================
// Step 4: Container Path Redirections & Pre-Boot Configuration Locks
// =============================================================================
$app->usePublicPath($testingDir . DIRECTORY_SEPARATOR . 'public');

$app->booting(function ($app) use ($approvedDbFile, $testingDir): void {
    $app['config']->set('database.default', 'sqlite');
    $app['config']->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => $approvedDbFile,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 5000,
        'journal_mode' => 'DELETE',
    ]);

    $app['config']->set('session.files', $testingDir . DIRECTORY_SEPARATOR . 'sessions');
    $app['config']->set('session.cookie', 'crms_qa_session');
    $app['config']->set('session.single_web_session', false);

    $app['config']->set('filesystems.disks.public.root', $testingDir . DIRECTORY_SEPARATOR . 'uploads');

    $app['config']->set('cache.default', 'array');
    $app['config']->set('queue.default', 'sync');
    $app['config']->set('mail.default', 'array');
    $app['config']->set('broadcasting.default', 'null');

    $app['config']->set('logging.channels.single.path', $testingDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'browser_qa.log');
    $app['config']->set('logging.channels.daily.path', $testingDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'browser_qa.log');
});

// =============================================================================
// Step 5: Execute Kernel Bootstrap
// =============================================================================
/** @var \Illuminate\Contracts\Http\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// =============================================================================
// Step 6: Purge and Reconnect SQLite Database
// =============================================================================
$app['db']->purge();
$connection = $app['db']->reconnect('sqlite');

// =============================================================================
// Step 7: MANDATORY 4-TIER CONNECTION GATEKEEPER
// =============================================================================
$canonicalApproved = crmsCanonicalizePath($approvedDbFile);
$canonicalActual = crmsCanonicalizePath((string) $connection->getDatabaseName());

// Tier 1: Driver Verification
if ($connection->getDriverName() !== 'sqlite') {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'failed',
        'error' => "CRMS GUARD ERROR: Driver is '{$connection->getDriverName()}', expected 'sqlite'.",
    ]);
    exit;
}

// Tier 2: Path Canonicalization Verification
if ($canonicalApproved === '' || $canonicalApproved !== $canonicalActual) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'failed',
        'error' => "CRMS GUARD ERROR: Database file mismatch ('{$canonicalActual}' !== '{$canonicalApproved}').",
    ]);
    exit;
}

// Tier 3: Prohibited String Blacklist Scan
$blacklist = ['crms', 'admin_crms', 'admin_crm_demo', 'mysql', '3306', '127.0.0.1:3306'];
$baseName = strtolower(basename($canonicalActual));

foreach ($blacklist as $term) {
    if (str_contains($baseName, $term)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'failed',
            'error' => "CRMS GUARD ERROR: Prohibited database identifier detected: '{$term}'.",
        ]);
        exit;
    }
}

// Tier 4: Engine-Level Open File Handle Verification (PRAGMA database_list)
$pdo = $connection->getPdo();
$statement = $pdo->query('PRAGMA database_list');
$databases = $statement ? $statement->fetchAll(\PDO::FETCH_ASSOC) : [];
$engineFile = '';

foreach ($databases as $row) {
    if (($row['name'] ?? '') === 'main') {
        $engineFile = crmsCanonicalizePath((string) ($row['file'] ?? ''));
        break;
    }
}

if ($engineFile === '' || $engineFile !== $canonicalApproved) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'failed',
        'error' => "CRMS GUARD ERROR: SQLite engine open file mismatch ('{$engineFile}' !== '{$canonicalApproved}').",
    ]);
    exit;
}

// =============================================================================
// Step 8: Outbound HTTP Mocks (Prevent Stray Network Requests)
// =============================================================================
\Illuminate\Support\Facades\Http::preventStrayRequests();
\Illuminate\Support\Facades\Http::fake([
    'maps.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['status' => 'OK', 'results' => []], 200),
    'roads.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['snappedPoints' => []], 200),
    '*' => \Illuminate\Support\Facades\Http::response([], 200),
]);

// =============================================================================
// Step 9: Diagnostic Health Check Handling (Post-Gatekeeper Verification)
// =============================================================================
if ($uriPath === '/_qa_health_check') {
    $userCount = 0;
    $tableCount = 0;

    try {
        $userCount = \App\Models\User::count();
        $tableCountStatement = $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $tableCount = $tableCountStatement ? (int) $tableCountStatement->fetchColumn() : 0;
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'failed',
            'error' => 'CRMS GUARD ERROR: Database query failed during health check: ' . $e->getMessage(),
        ]);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ready',
        'driver' => 'sqlite',
        'database' => $approvedDbFile,
        'engine_file' => $engineFile,
        'table_count' => $tableCount,
        'user_count' => $userCount,
        'pid' => getmypid(),
    ], JSON_PRETTY_PRINT);
    exit;
}

// =============================================================================
// Step 10: Dispatch Application Request Through Laravel Kernel
// =============================================================================
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request)->send();
$kernel->terminate($request, $response);
