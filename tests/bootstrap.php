<?php

declare(strict_types=1);

/**
 * Fail-closed guard for database-backed PHPUnit processes.
 *
 * This file must remain dependency-free so it runs before Laravel boots.
 * It validates configuration only; it never connects to a database.
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
    fwrite(STDERR, "CRMS test safety guard blocked execution: {$reason}" . PHP_EOL);
    exit(1);
};

$connection = $readEnvironment('DB_CONNECTION');
$database = $readEnvironment('DB_DATABASE');
$approvedConnection = $readEnvironment('CRMS_TEST_APPROVED_CONNECTION');
$approvedDatabase = $readEnvironment('CRMS_TEST_APPROVED_DATABASE');

if ($connection === null || $connection === '') {
    $fail('DB_CONNECTION is missing.');
}

if ($database === null || $database === '') {
    $fail('DB_DATABASE is missing.');
}

if ($database === 'admin_crms') {
    $fail('the production database name is not permitted.');
}

if ($approvedConnection === null || $approvedConnection !== $connection) {
    $fail('the effective database connection is not explicitly approved.');
}

if ($approvedDatabase === null || $approvedDatabase !== $database) {
    $fail('the effective database name is not explicitly approved.');
}

if ($connection === 'sqlite') {
    if ($database !== ':memory:') {
        $fail('only the approved in-memory SQLite target is permitted.');
    }

    if (! extension_loaded('pdo_sqlite')) {
        $fail('pdo_sqlite is unavailable for the approved SQLite target.');
    }
} elseif ($connection === 'mysql') {
    $host = $readEnvironment('DB_HOST');
    $approvedHost = $readEnvironment('CRMS_TEST_APPROVED_HOST');
    $databaseUrl = $readEnvironment('DB_URL');
    $username = $readEnvironment('DB_USERNAME');
    $password = $readEnvironment('DB_PASSWORD');

    if ($host === null || $host === '') {
        $fail('DB_HOST is missing for the MySQL target.');
    }

    if ($approvedHost === null || $approvedHost !== $host) {
        $fail('the effective MySQL host is not explicitly approved.');
    }

    if ($databaseUrl !== null && $databaseUrl !== '') {
        $fail('DB_URL is set and cannot be safely validated without resolving credentials.');
    }

    if ($username === null || $username === '' || $password === null || $password === '') {
        $fail('MySQL credentials are incomplete.');
    }

    if (! extension_loaded('pdo_mysql')) {
        $fail('pdo_mysql is unavailable for the MySQL target.');
    }
} else {
    $fail('the database connection driver is not approved.');
}

require dirname(__DIR__) . '/vendor/autoload.php';
