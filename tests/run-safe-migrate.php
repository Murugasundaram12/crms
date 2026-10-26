<?php

declare(strict_types=1);

/**
 * HouseFix360 CRM — Fail-Closed Safe Migration Runner for Dedicated Staging.
 *
 * This runner strictly restricts Artisan migration operations to the dedicated
 * staging database (crms_staging). It blocks all destructive commands (fresh, reset, rollback, wipe, seed, install),
 * strictly prohibits targeting crms or admin_crms, and validates all approval settings before Laravel boots.
 */

$readEnvironment = static function (string $key): ?string {
    $value = getenv($key);

    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    }

    if ($value === false && array_key_exists($key, $_SERVER)) {
        $value = $_SERVER[$key];
    }

    if ($value === false || $value === null) {
        return null;
    }

    return trim((string) $value);
};

$fail = static function (string $reason): never {
    fwrite(STDERR, "CRMS safe migration guard blocked execution: {$reason}" . PHP_EOL);
    exit(1);
};

// -----------------------------------------------------------------------------
// 1. Argument Parsing and Validation
// -----------------------------------------------------------------------------
$rawArgs = array_slice($argv, 1);
$preflightOnly = false;
$filteredArgs = [];
$positionalArgs = [];
$seenOptions = [];

$destructiveTerms = ['fresh', 'refresh', 'reset', 'rollback', 'wipe', 'seed', 'install'];
$allowedOptions = [
    '--preflight-only',
    '--pretend',
    '--path',
    '--realpath',
    '--step',
    '--database',
    '--env',
];

$skipNext = false;
$rawArgsCount = count($rawArgs);

for ($i = 0; $i < $rawArgsCount; $i++) {
    if ($skipNext) {
        $skipNext = false;
        continue;
    }

    $arg = $rawArgs[$i];
    $lower = strtolower($arg);

    // Check for destructive terms anywhere in the argument
    foreach ($destructiveTerms as $term) {
        if ($lower === "migrate:{$term}" || $lower === "db:{$term}" || str_contains($lower, $term)) {
            $fail("destructive or modifying operation '{$arg}' is strictly prohibited.");
        }
    }

    if ($arg === '--preflight-only') {
        $preflightOnly = true;
        continue;
    }

    if (! str_starts_with($arg, '-')) {
        $positionalArgs[] = $arg;
        $filteredArgs[] = $arg;
        continue;
    }

    // Handle options: check for dangerous flags
    if ($lower === '--force') {
        $fail("unsupported or dangerous argument '--force'.");
    }

    // Parse option name and value (supports --opt=val and --opt val)
    $optName = $arg;
    $optVal = null;

    if (str_contains($arg, '=')) {
        [$optName, $optVal] = explode('=', $arg, 2);
    } elseif ($arg === '--database' || $arg === '--env' || $arg === '--path') {
        if (isset($rawArgs[$i + 1]) && ! str_starts_with($rawArgs[$i + 1], '-')) {
            $optVal = $rawArgs[$i + 1];
            $skipNext = true;
        }
    }

    $optNameLower = strtolower($optName);

    // Check if option is allowed
    if (! in_array($optNameLower, $allowedOptions, true)) {
        $fail("unsupported option '{$arg}'.");
    }

    // Check for repeated options
    if (in_array($optNameLower, $seenOptions, true)) {
        $fail("repeated option '{$optName}' is not permitted.");
    }
    $seenOptions[] = $optNameLower;

    // Validate --database option
    if ($optNameLower === '--database') {
        if ($optVal === null || ! in_array(strtolower($optVal), ['mysql', 'mariadb'], true)) {
            $fail("unsupported database connection '{$optVal}'. Only 'mysql' and 'mariadb' are permitted.");
        }
        $filteredArgs[] = "--database={$optVal}";
        continue;
    }

    // Validate --env option
    if ($optNameLower === '--env') {
        if ($optVal === null || strtolower($optVal) !== 'staging') {
            $fail("unsupported environment '{$optVal}'. Only '--env=staging' is permitted.");
        }
        $filteredArgs[] = '--env=staging';
        continue;
    }

    $filteredArgs[] = $arg;
}

// Validate positional command
if (count($positionalArgs) > 1) {
    $fail('unexpected positional arguments: ' . implode(', ', array_slice($positionalArgs, 1)));
}

$command = $positionalArgs[0] ?? 'migrate';
$allowedCommands = ['migrate', 'migrate:status'];
if (! in_array($command, $allowedCommands, true)) {
    $fail("unsupported command '{$command}'. Only 'migrate' and 'migrate:status' are permitted.");
}

// -----------------------------------------------------------------------------
// 2. Database Identity and Approval Validation
// -----------------------------------------------------------------------------
$connection = $readEnvironment('DB_CONNECTION');
$database = $readEnvironment('DB_DATABASE');
$host = $readEnvironment('DB_HOST');
$port = $readEnvironment('DB_PORT');
$databaseUrl = $readEnvironment('DB_URL');
$username = $readEnvironment('DB_USERNAME');
$password = $readEnvironment('DB_PASSWORD');

$approvedConnection = $readEnvironment('CRMS_STAGING_APPROVED_CONNECTION');
$approvedDatabase = $readEnvironment('CRMS_STAGING_APPROVED_DATABASE');
$approvedHost = $readEnvironment('CRMS_STAGING_APPROVED_HOST');
$approvedPort = $readEnvironment('CRMS_STAGING_APPROVED_PORT');

if ($databaseUrl !== null && $databaseUrl !== '') {
    $fail('DB_URL is set and cannot be safely validated.');
}

if ($connection === null || $connection === '') {
    $fail('DB_CONNECTION is missing.');
}

if (! in_array($connection, ['mysql', 'mariadb'], true)) {
    $fail("DB_CONNECTION '{$connection}' is not an approved MySQL/MariaDB driver.");
}

if ($approvedConnection === null || $approvedConnection !== $connection) {
    $fail('the effective database connection driver is not explicitly approved.');
}

if ($database === null || $database === '') {
    $fail('DB_DATABASE is missing.');
}

$protectedDatabases = ['admin_crms', 'crms'];
if (in_array(strtolower($database), $protectedDatabases, true)) {
    $fail("the protected database '{$database}' is not permitted.");
}

if ($approvedDatabase !== null && in_array(strtolower($approvedDatabase), $protectedDatabases, true)) {
    $fail("the protected database '{$approvedDatabase}' cannot be approved for staging.");
}

if ($database !== 'crms_staging') {
    $fail("the target database must be exactly 'crms_staging', got '{$database}'.");
}

if ($approvedDatabase === null || $approvedDatabase !== $database) {
    $fail('the effective staging database name is not explicitly approved.');
}

if ($host === null || $host === '') {
    $fail('DB_HOST is missing.');
}

if ($host !== '127.0.0.1') {
    $fail("only local loopback host 127.0.0.1 is permitted, got '{$host}'.");
}

if ($approvedHost === null || $approvedHost !== $host) {
    $fail('the effective MySQL host is not explicitly approved.');
}

if ($port === null || $port === '') {
    $fail('DB_PORT is missing.');
}

if ($port !== '3306') {
    $fail("only default port 3306 is approved for staging, got '{$port}'.");
}

if ($approvedPort === null || $approvedPort !== $port) {
    $fail('the effective MySQL port is not explicitly approved.');
}

if ($username === null || $username === '' || $password === null || $password === '') {
    $fail('staging MySQL credentials are incomplete.');
}

if (! extension_loaded('pdo_mysql')) {
    $fail('pdo_mysql extension is unavailable.');
}

// -----------------------------------------------------------------------------
// 3. Preflight Execution Check
// -----------------------------------------------------------------------------
if ($preflightOnly) {
    fwrite(STDOUT, 'PREFLIGHT_OK: crms_staging configuration and command validated (no database connection attempted).' . PHP_EOL);
    exit(0);
}

// -----------------------------------------------------------------------------
// 4. Safe Boot and Execution (Active Migration Mode)
// -----------------------------------------------------------------------------
// Synchronize validated environment to process
putenv("DB_CONNECTION={$connection}");
putenv("DB_DATABASE={$database}");
putenv("DB_HOST={$host}");
putenv("DB_PORT={$port}");
putenv("DB_USERNAME={$username}");
putenv("DB_PASSWORD={$password}");

$_ENV['DB_CONNECTION'] = $connection;
$_ENV['DB_DATABASE'] = $database;
$_ENV['DB_HOST'] = $host;
$_ENV['DB_PORT'] = $port;
$_ENV['DB_USERNAME'] = $username;
$_ENV['DB_PASSWORD'] = $password;

$_SERVER['DB_CONNECTION'] = $connection;
$_SERVER['DB_DATABASE'] = $database;
$_SERVER['DB_HOST'] = $host;
$_SERVER['DB_PORT'] = $port;
$_SERVER['DB_USERNAME'] = $username;
$_SERVER['DB_PASSWORD'] = $password;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

// Verify Laravel's resolved database before executing command
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Determine the connection that Laravel's migrator will actually use
$cliDbOption = null;
foreach ($filteredArgs as $fArg) {
    if (str_starts_with($fArg, '--database=')) {
        $cliDbOption = substr($fArg, strlen('--database='));
        break;
    }
}

$effectiveConn = $cliDbOption ?? config('database.default');
if (! in_array($effectiveConn, ['mysql', 'mariadb'], true)) {
    $fail("migrator effective connection '{$effectiveConn}' is not an approved MySQL/MariaDB connection.");
}

$targetDb = config("database.connections.{$effectiveConn}.database");
if ($targetDb !== 'crms_staging') {
    $fail("Laravel resolved database '{$targetDb}' for connection '{$effectiveConn}' does not match staging target 'crms_staging'.");
}

if (in_array(strtolower((string) $targetDb), $protectedDatabases, true)) {
    $fail("CRITICAL: Laravel resolved protected database '{$targetDb}'. Halting immediately.");
}

$targetHost = config("database.connections.{$effectiveConn}.host");
if ($targetHost !== '127.0.0.1') {
    $fail("Laravel resolved host '{$targetHost}' for connection '{$effectiveConn}' is not 127.0.0.1.");
}

$targetPort = (string) config("database.connections.{$effectiveConn}.port");
if ($targetPort !== '3306') {
    $fail("Laravel resolved port '{$targetPort}' for connection '{$effectiveConn}' is not 3306.");
}

$artisanArgs = array_merge(['artisan'], $filteredArgs);
$status = $app->handleCommand(new \Symfony\Component\Console\Input\ArgvInput($artisanArgs));

exit($status);
