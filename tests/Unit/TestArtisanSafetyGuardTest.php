<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestArtisanSafetyGuardTest extends TestCase
{
    private string $artisanPath;
    private string $safeRunnerPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisanPath = dirname(__DIR__, 2) . '/artisan';
        $this->safeRunnerPath = dirname(__DIR__, 2) . '/tests/run-safe-migrate.php';
    }

    private function runArtisan(array $cliArgs, array $env = []): array
    {
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d display_errors=1 ' . escapeshellarg($this->artisanPath);

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
            $this->fail('Failed to spawn PHP process for artisan verification.');
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

    public function test_bare_artisan_blocks_direct_migrate(): void
    {
        $res = $this->runArtisan(['migrate']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate' is blocked.", $res['stderr']);
        $this->assertStringContainsString('php tests/run-safe-migrate.php migrate', $res['stderr']);
    }

    public function test_bare_artisan_blocks_direct_migrate_fresh(): void
    {
        $res = $this->runArtisan(['migrate:fresh']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate:fresh' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_blocks_direct_migrate_rollback(): void
    {
        $res = $this->runArtisan(['migrate:rollback']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate:rollback' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_blocks_direct_db_wipe(): void
    {
        $res = $this->runArtisan(['db:wipe']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'db:wipe' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_blocks_direct_db_seed(): void
    {
        $res = $this->runArtisan(['db:seed']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'db:seed' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_blocks_direct_migrate_status(): void
    {
        $res = $this->runArtisan(['migrate:status']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate:status' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_blocks_migration_with_preceding_options(): void
    {
        $res = $this->runArtisan(['--env=staging', 'migrate']);
        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate' is blocked.", $res['stderr']);
    }

    public function test_arbitrary_environment_variable_cannot_authorize_direct_migrations(): void
    {
        // Attempt forged environment variables
        $res = $this->runArtisan(['migrate'], [
            'CRMS_SAFE_MIGRATE_WRAPPER' => '1',
            'CRMS_SAFE_MIGRATE_AUTHORIZED' => '1',
            'ALLOW_LOCAL_MIGRATION' => '1',
            'APP_ENV' => 'staging',
        ]);

        $this->assertSame(1, $res['exitCode']);
        $this->assertStringContainsString("CRMS SAFETY GUARD: Direct Artisan execution of 'migrate' is blocked.", $res['stderr']);
    }

    public function test_bare_artisan_allows_ordinary_version_command(): void
    {
        $res = $this->runArtisan(['--version']);
        $this->assertSame(0, $res['exitCode'], 'Failed with stderr: ' . $res['stderr']);
        $this->assertStringContainsString('Laravel Framework', $res['stdout']);
    }

    public function test_bare_artisan_allows_ordinary_inspire_command(): void
    {
        $res = $this->runArtisan(['inspire']);
        $this->assertSame(0, $res['exitCode'], 'Failed with stderr: ' . $res['stderr']);
        $this->assertNotEmpty(trim($res['stdout']));
    }

    public function test_bare_artisan_allows_help_flag_on_migrate(): void
    {
        $res = $this->runArtisan(['migrate', '--help']);
        $this->assertSame(0, $res['exitCode'], 'Failed with stderr: ' . $res['stderr']);
        $this->assertStringContainsString('Run the database migrations', $res['stdout']);
    }

    public function test_approved_safe_wrapper_performs_preflight_without_database_connection(): void
    {
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php) . ' -d display_errors=1 ' . escapeshellarg($this->safeRunnerPath) . ' migrate --preflight-only';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = [
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: '',
            'PATHEXT' => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
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

        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__, 2), $env);
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, 'Preflight failed with stderr: ' . $stderr);
        $this->assertStringContainsString('PREFLIGHT_OK: crms_staging configuration and command validated (no database connection attempted).', (string) $stdout);
    }
}
