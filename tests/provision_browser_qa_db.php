<?php

declare(strict_types=1);

/**
 * HouseFix360 CRM — Disposable SQLite Browser QA Database Provisioner
 *
 * Prepares an isolated SQLite database file (storage/testing/browser_qa.sqlite)
 * for multi-request browser testing.
 *
 * Strict Security Rules:
 * 1. Prohibits running if an active QA server process (storage/testing/server.pid) is running.
 * 2. Requires explicit `--clean` flag to overwrite an existing database file.
 * 3. Asserts target is not an NTFS symbolic link or directory junction.
 * 4. Runs 4-tier engine gatekeeper before and after every migration and seeder step.
 * 5. Absolutely zero connection to persistent databases (crms, admin_crms, admin_crm_demo).
 */

define('CRMS_PROVISION_ACTIVE', true);

$workspaceRoot = dirname(__DIR__);
$testingDir = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'testing';
$approvedDbFile = $testingDir . DIRECTORY_SEPARATOR . 'browser_qa.sqlite';
$pidFile = $testingDir . DIRECTORY_SEPARATOR . 'server.pid';

// =============================================================================
// Helper Functions
// =============================================================================
function provisionFail(string $message): never
{
    fwrite(STDERR, "CRMS PROVISION ERROR: {$message}" . PHP_EOL);
    exit(1);
}

function provisionInfo(string $message): void
{
    fwrite(STDOUT, "[PROVISION] {$message}" . PHP_EOL);
}

function crmsCanonicalize(string $path): string
{
    if ($path === '' || ! file_exists($path)) {
        return '';
    }

    $real = realpath($path);

    return $real === false ? '' : strtolower(str_replace('/', '\\', $real));
}

// =============================================================================
// Step 1: Parse Command-Line Arguments
// =============================================================================
$rawArgs = array_slice($argv, 1);
$cleanRequested = in_array('--clean', $rawArgs, true) || in_array('--force', $rawArgs, true);
$preflightOnly = in_array('--preflight-only', $rawArgs, true);

// =============================================================================
// Step 2: Active Server Process Lock Verification
// =============================================================================
if (file_exists($pidFile)) {
    $activePid = trim((string) file_get_contents($pidFile));

    if ($activePid !== '' && ctype_digit($activePid)) {
        $pidNum = (int) $activePid;

        // Check if process is alive on Windows
        $processCheck = @shell_exec("powershell.exe -NoProfile -Command \"Get-Process -Id {$pidNum} -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Id\"");
        $isRunning = $processCheck !== null && trim($processCheck) === (string) $pidNum;

        if ($isRunning) {
            provisionFail("Active test server (PID {$pidNum}) is currently running. Stop the server before provisioning.");
        }

        // Stale PID file; clean it up safely
        @unlink($pidFile);
    }
}

// =============================================================================
// Step 3: Target File Verification & Safe Disposable Cleaning
// =============================================================================
if (file_exists($approvedDbFile)) {
    if (! $cleanRequested) {
        provisionFail("Target database file already exists at '{$approvedDbFile}'. Specify '--clean' to overwrite.");
    }

    // Symbolic link and directory junction protection
    if (is_link($approvedDbFile)) {
        provisionFail("Target path '{$approvedDbFile}' is a symbolic link or junction. Rejecting operation.");
    }

    // Canonical boundary verification
    $canonicalTarget = crmsCanonicalize($approvedDbFile);
    $canonicalTestingDir = crmsCanonicalize($testingDir);

    if ($canonicalTarget === '' || ! str_starts_with($canonicalTarget, $canonicalTestingDir . '\\')) {
        provisionFail("Canonical boundary violation: Target path resolves outside storage/testing.");
    }

    // Filename verification
    if (basename($canonicalTarget) !== 'browser_qa.sqlite') {
        provisionFail("Target base filename must be strictly 'browser_qa.sqlite'.");
    }

    // SQLite header signature verification (if non-zero)
    if (filesize($approvedDbFile) > 0) {
        $fp = fopen($approvedDbFile, 'rb');
        $header = $fp ? fread($fp, 16) : '';
        if ($fp) {
            fclose($fp);
        }

        if (! str_starts_with($header, "SQLite format 3\000")) {
            provisionFail("Existing target file is not a valid SQLite 3 database. Refusing to delete foreign file.");
        }
    }

    // Safely remove existing database and journal artifacts
    @unlink($approvedDbFile);
    @unlink($approvedDbFile . '-journal');
    @unlink($approvedDbFile . '-wal');
    @unlink($approvedDbFile . '-shm');
}

// Ensure testing directory exists
if (! is_dir($testingDir)) {
    mkdir($testingDir, 0755, true);
}

// Create fresh 0-byte SQLite database file
if (! touch($approvedDbFile)) {
    provisionFail("Failed to create 0-byte database file at '{$approvedDbFile}'. Check permissions.");
}

provisionInfo("Created clean 0-byte database file at: {$approvedDbFile}");

// =============================================================================
// Step 4: Environment Pre-Loading (Immutable Env Repository)
// =============================================================================
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('APP_CONFIG_CACHE=storage/testing/nonexistent_config_cache.php');
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
// Step 5: Boot Application in Console Mode
// =============================================================================
require $workspaceRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $workspaceRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

$app->usePublicPath($testingDir . DIRECTORY_SEPARATOR . 'public');

$app->booting(function ($app) use ($approvedDbFile): void {
    $app['config']->set('database.default', 'sqlite');
    $app['config']->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => $approvedDbFile,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 5000,
        'journal_mode' => 'DELETE',
    ]);
});

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// =============================================================================
// Step 6: 4-Tier Gatekeeper Assertion Helper
// =============================================================================
$assertDatabaseIdentity = function () use ($app, $approvedDbFile): void {
    $app['db']->purge();
    $connection = $app['db']->reconnect('sqlite');

    $canonicalApproved = crmsCanonicalize($approvedDbFile);
    $canonicalActual = crmsCanonicalize((string) $connection->getDatabaseName());

    if ($connection->getDriverName() !== 'sqlite') {
        provisionFail("Driver is '{$connection->getDriverName()}', expected 'sqlite'.");
    }

    if ($canonicalApproved === '' || $canonicalApproved !== $canonicalActual) {
        provisionFail("Database path mismatch ('{$canonicalActual}' !== '{$canonicalApproved}').");
    }

    $blacklist = ['crms', 'admin_crms', 'admin_crm_demo', 'mysql', '3306', '127.0.0.1:3306'];
    $baseName = strtolower(basename($canonicalActual));

    foreach ($blacklist as $term) {
        if (str_contains($baseName, $term)) {
            provisionFail("Prohibited database identifier detected: '{$term}'.");
        }
    }

    $pdo = $connection->getPdo();
    $statement = $pdo->query('PRAGMA database_list');
    $databases = $statement ? $statement->fetchAll(\PDO::FETCH_ASSOC) : [];
    $engineFile = '';

    foreach ($databases as $row) {
        if (($row['name'] ?? '') === 'main') {
            $engineFile = crmsCanonicalize((string) ($row['file'] ?? ''));
            break;
        }
    }

    if ($engineFile === '' || $engineFile !== $canonicalApproved) {
        provisionFail("SQLite engine open file mismatch ('{$engineFile}' !== '{$canonicalApproved}').");
    }
};

// Initial verification
$assertDatabaseIdentity();
provisionInfo('Pre-flight 4-tier database identity gatekeeper PASSED.');

if ($preflightOnly) {
    provisionInfo('PREFLIGHT_OK: Database file and connection gatekeeper verified (no migrations executed).');
    exit(0);
}

// =============================================================================
// Step 7: Run Schema Migrations
// =============================================================================
provisionInfo('Executing schema migrations (--database=sqlite --force)...');

$exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', [
    '--database' => 'sqlite',
    '--force' => true,
]);

$migrateOutput = trim(\Illuminate\Support\Facades\Artisan::output());

if ($exitCode !== 0) {
    provisionFail("Migration failed with exit code {$exitCode}:\n{$migrateOutput}");
}

provisionInfo("Migrations completed successfully.\n{$migrateOutput}");

// Re-verify connection identity post-migration
$assertDatabaseIdentity();

// =============================================================================
// Step 8: Seed Roles & Permissions
// =============================================================================
provisionInfo('Seeding system roles and permissions...');

$roles = ['Super Admin', 'Admin', 'Employee', 'Worker'];
foreach ($roles as $roleName) {
    \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
        ['name' => $roleName],
        ['created_at' => now(), 'updated_at' => now()]
    );
}

$permissions = [
    ['name' => 'View Dashboard', 'key' => 'dashboard.view'],
    ['name' => 'View Projects', 'key' => 'projects.view'],
    ['name' => 'Manage Projects', 'key' => 'projects.manage'],
    ['name' => 'View Reports', 'key' => 'reports.view'],
    ['name' => 'Manage Expenses', 'key' => 'expenses.manage'],
    ['name' => 'Manage Salaries', 'key' => 'salaries.manage'],
];

foreach ($permissions as $perm) {
    \Illuminate\Support\Facades\DB::table('permissions')->updateOrInsert(
        ['key' => $perm['key']],
        ['name' => $perm['name'], 'created_at' => now(), 'updated_at' => now()]
    );
}

// Assign all permissions to Super Admin role
$superAdminRoleId = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'Super Admin')->value('id');
$allPermIds = \Illuminate\Support\Facades\DB::table('permissions')->pluck('id');

foreach ($allPermIds as $permId) {
    \Illuminate\Support\Facades\DB::table('role_permission')->updateOrInsert(
        ['role_id' => $superAdminRoleId, 'permission_id' => $permId],
        ['created_at' => now(), 'updated_at' => now()]
    );
}

// Re-verify connection identity
$assertDatabaseIdentity();

// =============================================================================
// Step 9: Seed Synthetic QA Test Users
// =============================================================================
provisionInfo('Seeding synthetic QA users...');

$hashedPassword = \Illuminate\Support\Facades\Hash::make('password');

\Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
    ['email' => 'qa.admin@housefix.test'],
    [
        'name' => 'QA Super Administrator',
        'role' => 'Super Admin',
        'password' => $hashedPassword,
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]
);

$adminUserId = \Illuminate\Support\Facades\DB::table('users')->where('email', 'qa.admin@housefix.test')->value('id');

\Illuminate\Support\Facades\DB::table('role_user')->updateOrInsert(
    ['user_id' => $adminUserId, 'role_id' => $superAdminRoleId],
    ['created_at' => now(), 'updated_at' => now()]
);

\Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
    ['email' => 'qa.worker@housefix.test'],
    [
        'name' => 'QA Site Worker',
        'role' => 'Employee',
        'password' => $hashedPassword,
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]
);

// Re-verify connection identity
$assertDatabaseIdentity();

// =============================================================================
// Step 10: Seed Synthetic Master Data (Projects, Labours, Payment Methods)
// =============================================================================
provisionInfo('Seeding synthetic test master data...');

if (\Illuminate\Support\Facades\Schema::hasTable('payment_methods')) {
    $methods = ['Cash', 'Bank Transfer', 'UPI', 'Cheque'];
    foreach ($methods as $method) {
        \Illuminate\Support\Facades\DB::table('payment_methods')->updateOrInsert(
            ['name' => $method],
            ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}

if (\Illuminate\Support\Facades\Schema::hasTable('projects')) {
    \Illuminate\Support\Facades\DB::table('projects')->updateOrInsert(
        ['name' => 'Alpha Site QA Project'],
        [
            'client_name' => 'Test Client QA',
            'client_phone' => '9876543210',
            'status' => 'in_progress',
            'start_date' => now()->subDays(30)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
}

// Final connection verification
$assertDatabaseIdentity();

// =============================================================================
// Step 11: Final Post-Provisioning Checksum & Audit Report
// =============================================================================
$finalSize = filesize($approvedDbFile);
$sha256 = hash_file('sha256', $approvedDbFile);

$pdo = $app['db']->connection('sqlite')->getPdo();
$tableCount = (int) $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
$userCount = (int) \Illuminate\Support\Facades\DB::table('users')->count();

provisionInfo('=============================================================');
provisionInfo('PROVISIONING COMPLETE AND CERTIFIED:');
provisionInfo("  Database File: {$approvedDbFile}");
provisionInfo("  File Size:     {$finalSize} bytes");
provisionInfo("  SHA-256 Hash:  {$sha256}");
provisionInfo("  Total Tables:  {$tableCount}");
provisionInfo("  Total Users:   {$userCount}");
provisionInfo('=============================================================');
