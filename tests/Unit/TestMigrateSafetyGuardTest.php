<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestMigrateSafetyGuardTest extends TestCase
{
    private string $runnerPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runnerPath = dirname(__DIR__, 2) . '/tests/run-safe-migrate.php';
    }

    private function runGuard(array $env, array $cliArgs = ['--preflight-only']): array
    {
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d display_errors=1 ' . escapeshellarg($this->runnerPath);

        foreach ($cliArgs as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $baseEnv = [
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: '',
            'PATHEXT' => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
        ];

        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__, 2), array_merge($baseEnv, $env));

        if (! is_resource($process)) {
            $this->fail('Failed to spawn PHP process for migration guard verification.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => (string) $stdout,
            'stderr' => (string) $stderr,
        ];
    }

    private function validStagingEnv(): array
    {
        return [
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'crms_staging',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_USERNAME' => 'crms_staging_user',
            'DB_PASSWORD' => 'SyntheticStagingPass123!',
            'CRMS_STAGING_APPROVED_CONNECTION' => 'mysql',
            'CRMS_STAGING_APPROVED_DATABASE' => 'crms_staging',
            'CRMS_STAGING_APPROVED_HOST' => '127.0.0.1',
            'CRMS_STAGING_APPROVED_PORT' => '3306',
        ];
    }

    public function test_fails_when_db_connection_is_missing(): void
    {
        $env = $this->validStagingEnv();
        unset($env['DB_CONNECTION']);

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('DB_CONNECTION is missing.', $res['stderr']);
    }

    public function test_fails_when_connection_driver_is_not_mysql(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_CONNECTION'] = 'sqlite';
        $env['CRMS_STAGING_APPROVED_CONNECTION'] = 'sqlite';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("DB_CONNECTION 'sqlite' is not an approved MySQL/MariaDB driver.", $res['stderr']);
    }

    public function test_fails_when_approved_connection_mismatches(): void
    {
        $env = $this->validStagingEnv();
        $env['CRMS_STAGING_APPROVED_CONNECTION'] = 'mariadb';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('the effective database connection driver is not explicitly approved.', $res['stderr']);
    }

    public function test_fails_when_db_database_is_missing(): void
    {
        $env = $this->validStagingEnv();
        unset($env['DB_DATABASE']);

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('DB_DATABASE is missing.', $res['stderr']);
    }

    public function test_fails_when_targeting_protected_crms(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_DATABASE'] = 'crms';
        $env['CRMS_STAGING_APPROVED_DATABASE'] = 'crms';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("the protected database 'crms' is not permitted.", $res['stderr']);
    }

    public function test_fails_when_targeting_protected_admin_crms(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_DATABASE'] = 'admin_crms';
        $env['CRMS_STAGING_APPROVED_DATABASE'] = 'admin_crms';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("the protected database 'admin_crms' is not permitted.", $res['stderr']);
    }

    public function test_fails_when_approved_database_attempts_to_approve_protected_db(): void
    {
        $env = $this->validStagingEnv();
        $env['CRMS_STAGING_APPROVED_DATABASE'] = 'crms';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("the protected database 'crms' cannot be approved for staging.", $res['stderr']);
    }

    public function test_fails_when_target_database_is_not_crms_staging(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_DATABASE'] = 'some_random_db';
        $env['CRMS_STAGING_APPROVED_DATABASE'] = 'some_random_db';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("the target database must be exactly 'crms_staging', got 'some_random_db'.", $res['stderr']);
    }

    public function test_fails_when_approved_database_mismatches(): void
    {
        $env = $this->validStagingEnv();
        $env['CRMS_STAGING_APPROVED_DATABASE'] = 'other_staging';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('the effective staging database name is not explicitly approved.', $res['stderr']);
    }

    public function test_fails_when_host_is_not_loopback_ip(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_HOST'] = '192.168.1.100';
        $env['CRMS_STAGING_APPROVED_HOST'] = '192.168.1.100';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("only local loopback host 127.0.0.1 is permitted, got '192.168.1.100'.", $res['stderr']);
    }

    public function test_fails_when_approved_host_mismatches(): void
    {
        $env = $this->validStagingEnv();
        $env['CRMS_STAGING_APPROVED_HOST'] = '10.0.0.1';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('the effective MySQL host is not explicitly approved.', $res['stderr']);
    }

    public function test_fails_when_port_is_not_3306(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_PORT'] = '3307';
        $env['CRMS_STAGING_APPROVED_PORT'] = '3307';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("only default port 3306 is approved for staging, got '3307'.", $res['stderr']);
    }

    public function test_fails_when_approved_port_mismatches(): void
    {
        $env = $this->validStagingEnv();
        $env['CRMS_STAGING_APPROVED_PORT'] = '3308';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('the effective MySQL port is not explicitly approved.', $res['stderr']);
    }

    public function test_fails_when_credentials_are_incomplete(): void
    {
        $env = $this->validStagingEnv();
        unset($env['DB_PASSWORD']);

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('staging MySQL credentials are incomplete.', $res['stderr']);
    }

    public function test_fails_when_db_url_is_set(): void
    {
        $env = $this->validStagingEnv();
        $env['DB_URL'] = 'mysql://user:pass@127.0.0.1:3306/crms_staging';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('DB_URL is set and cannot be safely validated.', $res['stderr']);
    }

    public function test_fails_when_running_destructive_migrate_fresh(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:fresh', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'migrate:fresh' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_destructive_migrate_refresh(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:refresh', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'migrate:refresh' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_destructive_migrate_reset(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:reset', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'migrate:reset' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_destructive_migrate_rollback(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:rollback', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'migrate:rollback' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_destructive_migrate_install(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:install', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'migrate:install' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_destructive_db_wipe(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['db:wipe', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'db:wipe' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_running_db_seed(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['db:seed', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation 'db:seed' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_passing_seed_flag(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--seed', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("destructive or modifying operation '--seed' is strictly prohibited.", $res['stderr']);
    }

    public function test_fails_when_passing_force_flag(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--force', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("unsupported or dangerous argument '--force'.", $res['stderr']);
    }

    public function test_fails_when_passing_space_separated_database_sqlite(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--database', 'sqlite', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("unsupported database connection 'sqlite'. Only 'mysql' and 'mariadb' are permitted.", $res['stderr']);
    }

    public function test_fails_when_passing_repeated_database_options(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--database=mysql', '--database=mariadb', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("repeated option '--database' is not permitted.", $res['stderr']);
    }

    public function test_fails_when_passing_unapproved_env_option(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--env=local', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("unsupported environment 'local'. Only '--env=staging' is permitted.", $res['stderr']);
    }

    public function test_fails_when_passing_repeated_env_options(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--env=staging', '--env=staging', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("repeated option '--env' is not permitted.", $res['stderr']);
    }

    public function test_fails_when_passing_unexpected_positional_arguments(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', 'extra_arg', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('unexpected positional arguments: extra_arg', $res['stderr']);
    }

    public function test_fails_when_passing_unsupported_options(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--isolated', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("unsupported option '--isolated'.", $res['stderr']);
    }

    public function test_fails_when_passing_unsupported_artisan_command(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['tinker', '--preflight-only']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("unsupported command 'tinker'. Only 'migrate' and 'migrate:status' are permitted.", $res['stderr']);
    }

    public function test_passes_preflight_when_valid_staging_env_and_command(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--preflight-only']);
        $this->assertSame(0, $res['exitCode'], 'Preflight failed with stderr: ' . $res['stderr']);
        $this->assertStringContainsString('PREFLIGHT_OK: crms_staging configuration and command validated (no database connection attempted).', $res['stdout']);
    }

    public function test_passes_preflight_for_migrate_status(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate:status', '--preflight-only']);
        $this->assertSame(0, $res['exitCode'], 'Preflight failed with stderr: ' . $res['stderr']);
        $this->assertStringContainsString('PREFLIGHT_OK: crms_staging configuration and command validated (no database connection attempted).', $res['stdout']);
    }

    public function test_passes_preflight_with_pretend_flag_and_env_staging(): void
    {
        $res = $this->runGuard($this->validStagingEnv(), ['migrate', '--pretend', '--env=staging', '--preflight-only']);
        $this->assertSame(0, $res['exitCode'], 'Preflight failed with stderr: ' . $res['stderr']);
        $this->assertStringContainsString('PREFLIGHT_OK: crms_staging configuration and command validated (no database connection attempted).', $res['stdout']);
    }

    public function test_fails_when_test_approvals_cannot_substitute_for_staging_approvals(): void
    {
        $env = $this->validStagingEnv();
        // Remove staging approval keys and substitute test approval keys
        unset(
            $env['CRMS_STAGING_APPROVED_CONNECTION'],
            $env['CRMS_STAGING_APPROVED_DATABASE'],
            $env['CRMS_STAGING_APPROVED_HOST'],
            $env['CRMS_STAGING_APPROVED_PORT']
        );
        $env['CRMS_TEST_APPROVED_CONNECTION'] = 'mysql';
        $env['CRMS_TEST_APPROVED_DATABASE'] = 'crms_staging';
        $env['CRMS_TEST_APPROVED_HOST'] = '127.0.0.1';
        $env['CRMS_TEST_APPROVED_PORT'] = '3306';

        $res = $this->runGuard($env);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString('the effective database connection driver is not explicitly approved.', $res['stderr']);
    }

    public function test_staging_example_template_contains_both_complete_key_sets(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/.env.staging.example';
        $this->assertFileExists($templatePath);
        $content = (string) file_get_contents($templatePath);

        // Verify staging migration approval keys
        $this->assertStringContainsString('CRMS_STAGING_APPROVED_CONNECTION=mysql', $content);
        $this->assertStringContainsString('CRMS_STAGING_APPROVED_DATABASE=crms_staging', $content);
        $this->assertStringContainsString('CRMS_STAGING_APPROVED_HOST=127.0.0.1', $content);
        $this->assertStringContainsString('CRMS_STAGING_APPROVED_PORT=3306', $content);

        // Verify test safety approval keys
        $this->assertStringContainsString('CRMS_TEST_APPROVED_CONNECTION=mysql', $content);
        $this->assertStringContainsString('CRMS_TEST_APPROVED_DATABASE=crms_staging', $content);
        $this->assertStringContainsString('CRMS_TEST_APPROVED_HOST=127.0.0.1', $content);
        $this->assertStringContainsString('CRMS_TEST_APPROVED_PORT=3306', $content);
    }
}
