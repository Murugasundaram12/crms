<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestDatabaseSafetyGuardTest extends TestCase
{
    private string $bootstrapPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootstrapPath = dirname(__DIR__, 2) . '/tests/bootstrap.php';
    }

    private function runGuard(array $env): array
    {
        $php = PHP_BINARY;
        $code = 'require ' . var_export($this->bootstrapPath, true) . "; echo 'BOOTSTRAP_SUCCESS';";
        $cmd = escapeshellarg($php) . ' -d display_errors=1 -r ' . escapeshellarg($code);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Clean inherited environment to ensure pure test isolation
        $baseEnv = [
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: '',
            'PATHEXT' => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
        ];

        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__, 2), array_merge($baseEnv, $env));

        if (! is_resource($process)) {
            $this->fail('Failed to spawn PHP process for safety guard verification.');
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

    public function test_fails_when_db_connection_is_missing(): void
    {
        $result = $this->runGuard([
            'DB_DATABASE' => ':memory:',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('DB_CONNECTION is missing.', $result['stderr']);
    }

    public function test_fails_when_db_database_is_missing(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'sqlite',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('DB_DATABASE is missing.', $result['stderr']);
    }

    public function test_fails_when_targeting_admin_crms(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'admin_crms',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => 'admin_crms',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString("the protected database 'admin_crms' is not permitted.", $result['stderr']);
    }

    public function test_fails_when_targeting_crms(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'crms',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => 'crms',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString("the protected database 'crms' is not permitted.", $result['stderr']);
    }

    public function test_fails_when_approved_database_attempts_to_approve_protected_database(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'some_db',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => 'crms',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString("the protected database 'crms' cannot be approved for tests.", $result['stderr']);
    }

    public function test_fails_when_connection_is_not_approved(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => ':memory:',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('the effective database connection is not explicitly approved.', $result['stderr']);
    }

    public function test_fails_when_database_is_not_approved(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CRMS_TEST_APPROVED_CONNECTION' => 'sqlite',
            'CRMS_TEST_APPROVED_DATABASE' => 'crms_staging',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('the effective database name is not explicitly approved.', $result['stderr']);
    }

    public function test_fails_when_sqlite_database_is_not_memory(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'database.sqlite',
            'CRMS_TEST_APPROVED_CONNECTION' => 'sqlite',
            'CRMS_TEST_APPROVED_DATABASE' => 'database.sqlite',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('only the approved in-memory SQLite target is permitted.', $result['stderr']);
    }

    public function test_fails_when_mysql_target_has_unapproved_host(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'crms_staging',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => 'crms_staging',
            'DB_HOST' => '127.0.0.1',
            'CRMS_TEST_APPROVED_HOST' => '192.168.1.100',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('the effective MySQL host is not explicitly approved.', $result['stderr']);
    }

    public function test_fails_when_mysql_target_has_unapproved_port(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'crms_staging',
            'CRMS_TEST_APPROVED_CONNECTION' => 'mysql',
            'CRMS_TEST_APPROVED_DATABASE' => 'crms_staging',
            'DB_HOST' => '127.0.0.1',
            'CRMS_TEST_APPROVED_HOST' => '127.0.0.1',
            'DB_PORT' => '3307',
            'CRMS_TEST_APPROVED_PORT' => '3306',
        ]);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('the effective MySQL port is not explicitly approved.', $result['stderr']);
    }

    public function test_passes_when_using_approved_sqlite_memory_target(): void
    {
        $result = $this->runGuard([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CRMS_TEST_APPROVED_CONNECTION' => 'sqlite',
            'CRMS_TEST_APPROVED_DATABASE' => ':memory:',
        ]);

        $this->assertSame(0, $result['exitCode'], 'Expected exit code 0, got: ' . $result['stderr']);
        $this->assertStringContainsString('BOOTSTRAP_SUCCESS', $result['stdout']);
    }
}
