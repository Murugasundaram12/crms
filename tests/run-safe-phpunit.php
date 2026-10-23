<?php

declare(strict_types=1);

/**
 * Controlled PHPUnit entry point for the CRMS project.
 *
 * The caller must provide the database target and explicit approval variables
 * through the process environment. Alternate PHPUnit configurations and
 * bootstrap scripts are rejected so this wrapper cannot silently bypass the
 * project safety guard.
 */
$arguments = array_slice($argv, 1);

foreach ($arguments as $index => $argument) {
    if (
        $argument === '--bootstrap'
        || str_starts_with($argument, '--bootstrap=')
        || $argument === '--configuration'
        || str_starts_with($argument, '--configuration=')
        || $argument === '-c'
        || str_starts_with($argument, '-c=')
        || $argument === '--no-configuration'
    ) {
        fwrite(STDERR, "CRMS safe PHPUnit wrapper blocked an alternate configuration or bootstrap." . PHP_EOL);
        exit(1);
    }
}

require __DIR__ . '/bootstrap.php';

$phpunit = dirname(__DIR__) . '/vendor/bin/phpunit';
$configuration = dirname(__DIR__) . '/phpunit.xml';
$command = array_merge([
    PHP_BINARY,
    $phpunit,
    '--configuration',
    $configuration,
], $arguments);

$escapedCommand = implode(' ', array_map('escapeshellarg', $command));
passthru($escapedCommand, $exitCode);

exit($exitCode);
