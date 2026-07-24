<?php
// Boot Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== Environment Info ===\n";
echo "Current User: " . get_current_user() . "\n";
echo "Database Connection Default: " . config('database.default') . "\n";
$connName = config('database.default');
echo "Database Host: " . config("database.connections.{$connName}.host") . "\n";
echo "Database Name: " . config("database.connections.{$connName}.database") . "\n";
echo "Database Username: " . config("database.connections.{$connName}.username") . "\n";

try {
    $dbName = Illuminate\Support\Facades\DB::connection()->getDatabaseName();
    echo "Active DB Name: $dbName\n";
    
    // Check tables
    $tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
    echo "Tables count: " . count($tables) . "\n";
    
    // Check if preorders table exists
    $hasPreorders = false;
    foreach ($tables as $t) {
        $tArray = (array)$t;
        $tName = reset($tArray);
        if ($tName === 'preorders') {
            $hasPreorders = true;
            break;
        }
    }
    
    if ($hasPreorders) {
        $count = Illuminate\Support\Facades\DB::table('preorders')->count();
        echo "Preorders table exists! Total rows: $count\n";
        if ($count > 0) {
            $first = Illuminate\Support\Facades\DB::table('preorders')->first();
            echo "First preorder details:\n";
            print_r($first);
        }
    } else {
        echo "Preorders table DOES NOT EXIST in this database!\n";
    }
} catch (\Throwable $e) {
    echo "DB Query Error: " . $e->getMessage() . "\n";
}

echo "\n=== Laravel Log Check ===\n";
$logPath = storage_path('logs/laravel.log');
echo "Log file: $logPath\n";
echo "Log file exists: " . (file_exists($logPath) ? "YES" : "NO") . "\n";
echo "Log file writable: " . (is_writable($logPath) ? "YES" : "NO") . "\n";
if (file_exists($logPath)) {
    echo "Log file size: " . filesize($logPath) . " bytes\n";
    $lines = file($logPath);
    $last_lines = array_slice($lines, -15);
    echo "Last 15 lines of live laravel.log:\n";
    echo implode("", $last_lines) . "\n";
}
