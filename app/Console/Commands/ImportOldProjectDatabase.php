<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class ImportOldProjectDatabase extends Command
{
    protected $signature = 'old-db:import
        {--dump=old_project_db.sql : Old project SQL dump path}
        {--temp-db= : Temporary database name used while importing}
        {--fresh-temp : Drop and recreate the temporary database before import}
        {--skip-temp-import : Reuse an already imported temporary database}
        {--truncate : Truncate mapped current-project tables before importing}
        {--execute : Actually write data into the current database}';

    protected $description = 'Import old CRM database dump into the current CRM schema through a guarded mapping.';

    private string $tempDatabase;

    public function handle(): int
    {
        $dumpPath = base_path((string) $this->option('dump'));
        $this->tempDatabase = $this->sanitizeIdentifier(
            (string) ($this->option('temp-db') ?: config('database.connections.mysql.database') . '_old_import')
        );

        if (! is_file($dumpPath) && ! $this->option('skip-temp-import')) {
            $this->error("Dump file not found: {$dumpPath}");
            return self::FAILURE;
        }

        $this->warn($this->option('execute')
            ? 'EXECUTE mode: current database data may be inserted/updated.'
            : 'DRY-RUN mode: no current database data will be changed. Add --execute to import.');

        try {
            if (! $this->option('skip-temp-import')) {
                $this->prepareTemporaryDatabase($dumpPath);
            }

            $this->printSourceSummary();

            if (! $this->option('execute')) {
                $this->info('Dry-run complete. Review the summary, then run again with --execute when ready.');
                return self::SUCCESS;
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $this->ensureTargetAutoIncrement();

            if ($this->option('truncate')) {
                $this->truncateTargetTables();
            }

            $this->importMappedData();
            $this->runPostImportMaintenance();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Old database import completed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function prepareTemporaryDatabase(string $dumpPath): void
    {
        $this->line("Preparing temporary database [{$this->tempDatabase}]...");

        if ($this->option('fresh-temp')) {
            DB::statement("DROP DATABASE IF EXISTS `{$this->tempDatabase}`");
        }

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$this->tempDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $pdo = $this->temporaryPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $sql = file_get_contents($dumpPath);
        $statements = $this->splitSqlStatements($sql ?: '');
        $bar = $this->output->createProgressBar(count($statements));
        $bar->start();

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                $bar->advance();
                continue;
            }

            $pdo->exec($statement);
            $bar->advance();
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $bar->finish();
        $this->newLine(2);
    }

    private function temporaryPdo(): PDO
    {
        $config = config('database.connections.mysql');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $this->tempDatabase,
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function printSourceSummary(): void
    {
        $tables = [
            'users',
            'roles',
            'model_has_roles',
            'clientdetails',
            'project_details',
            'main_category',
            'category',
            'labour_role',
            'labour_details',
            'vendor_details',
            'stage',
            'expenses',
            'expenses_unpaid_date',
            'advance_history',
            'wallet',
            'transferdetails',
        ];

        $rows = [];
        foreach ($tables as $table) {
            $rows[] = [$table, $this->sourceTableExists($table) ? $this->sourceCount($table) : 'missing'];
        }

        $this->table(['Old table', 'Rows'], $rows);
    }

    private function truncateTargetTables(): void
    {
        $this->warn('Truncating mapped target tables...');

        foreach ([
            'user_roles',
            'advance_history',
            'expenses_unpaid_date',
            'wallet',
            'expenses',
            'payment_stages',
            'projects',
            'vendors',
            'labours',
            'labour_roles',
            'categories',
            'main_categories',
            'clients',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
    }

    private function ensureTargetAutoIncrement(): void
    {
        foreach ([
            'users',
            'roles',
            'clients',
            'main_categories',
            'categories',
            'labour_roles',
            'labours',
            'vendors',
            'projects',
            'payment_stages',
            'expenses',
            'expenses_unpaid_date',
            'advance_history',
            'wallet',
            'transferdetails',
            'user_roles',
        ] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }

    private function importMappedData(): void
    {
        $this->importRoles();
        $this->importUsers();
        $this->importUserRoles();
        $this->importClients();
        $this->importCategories();
        $this->importLabours();
        $this->importVendors();
        $this->importProjects();
        $this->importPaymentStages();
        $this->importExpenses();
        $this->importExpenseSettlements();
        $this->importAdvanceHistory();
        $this->importWallet();
        $this->importTransfers();
    }

    private function importRoles(): void
    {
        if (! $this->sourceTableExists('roles')) {
            return;
        }

        $this->runMapping('roles', "
            INSERT INTO roles (id, name, description, created_at, updated_at)
            SELECT id, name, CONCAT('Imported from old DB guard: ', guard_name), COALESCE(created_at, NOW()), COALESCE(updated_at, NOW())
            FROM `{$this->tempDatabase}`.roles
            ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), updated_at = VALUES(updated_at)
        ");
    }

    private function importUsers(): void
    {
        if (! $this->sourceTableExists('users')) {
            return;
        }

        $this->runMapping('users', "
            INSERT INTO users (
                id, name, email, phone, designation, role, address, hourly_rate, hire_date, status,
                wallet, avatar, email_verified_at, password, remember_token, created_at, updated_at
            )
            SELECT
                u.id,
                COALESCE(NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), ''), u.email),
                u.email,
                NULLIF(u.phone, ''),
                NULLIF(u.job_title, ''),
                COALESCE((
                    SELECT r.name
                    FROM `{$this->tempDatabase}`.model_has_roles m
                    INNER JOIN `{$this->tempDatabase}`.roles r ON r.id = m.role_id
                    WHERE m.model_id = u.id
                    LIMIT 1
                ), 'Employee'),
                u.address,
                0,
                DATE(u.date_of_joining),
                IF(u.active_status = 1 AND u.delete_status = 0, 'active', 'inactive'),
                COALESCE(u.wallet, 0),
                NULLIF(u.image, ''),
                u.email_verified_at,
                u.password,
                u.remember_token,
                u.created_at,
                u.updated_at
            FROM `{$this->tempDatabase}`.users u
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), phone = VALUES(phone), designation = VALUES(designation), role = VALUES(role),
                address = VALUES(address), hire_date = VALUES(hire_date), status = VALUES(status),
                wallet = VALUES(wallet), avatar = VALUES(avatar), updated_at = VALUES(updated_at)
        ");
    }

    private function importUserRoles(): void
    {
        if (! $this->sourceTableExists('model_has_roles') || ! $this->sourceTableExists('roles')) {
            return;
        }

        $this->runMapping('user_roles', "
            INSERT IGNORE INTO user_roles (user_id, role_id, created_at, updated_at)
            SELECT m.model_id, nr.id, NOW(), NOW()
            FROM `{$this->tempDatabase}`.model_has_roles m
            INNER JOIN `{$this->tempDatabase}`.roles old_roles ON old_roles.id = m.role_id
            INNER JOIN roles nr ON nr.name = old_roles.name
            INNER JOIN users u ON u.id = m.model_id
        ");
    }

    private function importClients(): void
    {
        if (! $this->sourceTableExists('clientdetails')) {
            return;
        }

        $this->runMapping('clients', "
            INSERT INTO clients (id, name, company_name, email, phone, address, status, created_at, updated_at)
            SELECT
                id,
                COALESCE(NULLIF(TRIM(CONCAT(first_name, ' ', last_name)), ''), company_name, CONCAT('Client ', id)),
                NULLIF(company_name, ''),
                NULLIF(email, ''),
                NULLIF(phone, ''),
                address,
                IF(active_status = 1 AND delete_status = 0, 'active', 'inactive'),
                created_at,
                updated_at
            FROM `{$this->tempDatabase}`.clientdetails
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), company_name = VALUES(company_name), phone = VALUES(phone),
                address = VALUES(address), status = VALUES(status), updated_at = VALUES(updated_at)
        ");
    }

    private function importCategories(): void
    {
        if ($this->sourceTableExists('main_category')) {
            $this->runMapping('main_categories', "
                INSERT INTO main_categories (id, name, status, created_at, updated_at)
                SELECT id, name, IF(status = 1 AND deleted_at IS NULL, 'active', 'inactive'), created_at, updated_at
                FROM `{$this->tempDatabase}`.main_category
                ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), updated_at = VALUES(updated_at)
            ");
        }

        if ($this->sourceTableExists('category')) {
            $this->runMapping('categories', "
                INSERT INTO categories (id, main_category_id, name, created_at, updated_at)
                SELECT id, main_category_id, name, created_at, updated_at
                FROM `{$this->tempDatabase}`.category
                WHERE main_category_id IS NULL OR main_category_id IN (SELECT id FROM main_categories)
                ON DUPLICATE KEY UPDATE main_category_id = VALUES(main_category_id), name = VALUES(name), updated_at = VALUES(updated_at)
            ");
        }
    }

    private function importLabours(): void
    {
        if ($this->sourceTableExists('labour_role')) {
            $this->runMapping('labour_roles', "
                INSERT INTO labour_roles (id, name, salary_type, salary, created_at, updated_at)
                SELECT
                    id,
                    name,
                    CASE salary_type WHEN 1 THEN 'weekly' WHEN 2 THEN 'monthly' ELSE 'daily' END,
                    CAST(COALESCE(NULLIF(salary, ''), 0) AS DECIMAL(12,2)),
                    created_at,
                    updated_at
                FROM `{$this->tempDatabase}`.labour_role
                WHERE deleted_at IS NULL
                ON DUPLICATE KEY UPDATE name = VALUES(name), salary_type = VALUES(salary_type), salary = VALUES(salary), updated_at = VALUES(updated_at)
            ");
        }

        DB::statement("
            INSERT IGNORE INTO labour_roles (name, salary_type, salary, created_at, updated_at)
            VALUES ('General', 'daily', 0, NOW(), NOW())
        ");

        if ($this->sourceTableExists('labour_details')) {
            $this->runMapping('labours', "
                INSERT INTO labours (
                    id, name, job_title, phone, phone_number, labour_role_id, gender, salary,
                    advance_amt, labour_role, government_photo, government_image, created_at, updated_at, deleted_at
                )
                SELECT
                    l.id,
                    l.name,
                    NULLIF(l.job_title, ''),
                    NULLIF(l.phone, ''),
                    COALESCE(NULLIF(l.phone, ''), '-'),
                    COALESCE((SELECT lr.id FROM labour_roles lr WHERE lr.id = l.labour_role), (SELECT MIN(id) FROM labour_roles)),
                    CASE l.gender WHEN 2 THEN 'female' WHEN 3 THEN 'other' ELSE 'male' END,
                    l.salary,
                    COALESCE(l.advance_amt, 0),
                    CAST(l.labour_role AS CHAR),
                    NULLIF(l.government_image, ''),
                    NULLIF(l.government_image, ''),
                    l.created_at,
                    l.updated_at,
                    l.deleted_at
                FROM `{$this->tempDatabase}`.labour_details l
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name), job_title = VALUES(job_title), phone = VALUES(phone),
                    phone_number = VALUES(phone_number), labour_role_id = VALUES(labour_role_id),
                    gender = VALUES(gender), salary = VALUES(salary), advance_amt = VALUES(advance_amt),
                    government_photo = VALUES(government_photo), government_image = VALUES(government_image),
                    updated_at = VALUES(updated_at), deleted_at = VALUES(deleted_at)
            ");
        }
    }

    private function importVendors(): void
    {
        if (! $this->sourceTableExists('vendor_details')) {
            return;
        }

        $this->runMapping('vendors', "
            INSERT INTO vendors (id, name, address, phone, advance_amount, advance_amt, created_at, updated_at)
            SELECT id, name, address, NULLIF(phone, ''), CAST(COALESCE(NULLIF(advance_amt, ''), 0) AS DECIMAL(14,2)),
                   CAST(COALESCE(NULLIF(advance_amt, ''), 0) AS DECIMAL(14,2)), created_at, updated_at
            FROM `{$this->tempDatabase}`.vendor_details
            WHERE deleted_at IS NULL
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), address = VALUES(address), phone = VALUES(phone),
                advance_amount = VALUES(advance_amount), advance_amt = VALUES(advance_amt), updated_at = VALUES(updated_at)
        ");
    }

    private function importProjects(): void
    {
        if (! $this->sourceTableExists('project_details')) {
            return;
        }

        $this->runMapping('projects', "
            INSERT INTO projects (
                id, name, project_code, client_id, manager_id, type, priority, status, progress,
                start_date, end_date, location, advance_amt, profit, description, created_at, updated_at
            )
            SELECT
                p.id,
                p.name,
                CONCAT('PRJ-', LPAD(p.id, 5, '0')),
                COALESCE((SELECT c.id FROM clients c WHERE c.id = p.client_id), (SELECT MIN(c2.id) FROM clients c2)),
                NULL,
                'Construction',
                'medium',
                CASE p.project_status WHEN 1 THEN 'active' WHEN 2 THEN 'completed' ELSE 'planning' END,
                CASE p.project_status WHEN 2 THEN 100 ELSE 0 END,
                DATE(p.start_date),
                DATE(p.end_date),
                NULL,
                COALESCE(p.advance_amt, 0),
                COALESCE(p.profit, 0),
                CONCAT('Legacy total amount: ', COALESCE(p.total_amt, 0), '; payment mode: ', COALESCE(p.payment_mode, 0)),
                p.created_at,
                p.updated_at
            FROM `{$this->tempDatabase}`.project_details p
            WHERE p.delete_status = 0
              AND COALESCE((SELECT c.id FROM clients c WHERE c.id = p.client_id), (SELECT MIN(c2.id) FROM clients c2)) IS NOT NULL
            ON DUPLICATE KEY UPDATE
                name = VALUES(name), client_id = VALUES(client_id), status = VALUES(status), progress = VALUES(progress),
                start_date = VALUES(start_date), end_date = VALUES(end_date), advance_amt = VALUES(advance_amt),
                profit = VALUES(profit), description = VALUES(description), updated_at = VALUES(updated_at)
        ");
    }

    private function importPaymentStages(): void
    {
        if (! $this->sourceTableExists('stage') || ! Schema::hasTable('payment_stages')) {
            return;
        }

        $nameColumn = Schema::hasColumn('payment_stages', 'name') ? 'name' : 'stage_name';
        $extraColumns = Schema::hasColumn('payment_stages', 'project_id') ? ', project_id' : '';
        $extraSelect = Schema::hasColumn('payment_stages', 'project_id') ? ', (SELECT MIN(id) FROM projects)' : '';

        $this->runMapping('payment_stages', "
            INSERT INTO payment_stages (id, {$nameColumn}, created_at, updated_at {$extraColumns})
            SELECT id, name, created_at, updated_at {$extraSelect}
            FROM `{$this->tempDatabase}`.stage
            WHERE delete_status = 0
            ON DUPLICATE KEY UPDATE {$nameColumn} = VALUES({$nameColumn}), updated_at = VALUES(updated_at)
        ");
    }

    private function importExpenses(): void
    {
        if (! $this->sourceTableExists('expenses')) {
            return;
        }

        $this->runMapping('expenses', "
            INSERT INTO expenses (
                id, amount, main_category_id, category_id, project_id, user_id, `current_date`, description,
                paid_amt, unpaid_amt, extra_amt, image, editedBy, payment_mode, created_at, updated_at,
                deleted_at, reason, labour_id, vendor_id, is_advance
            )
            SELECT
                e.id,
                e.amount,
                e.main_category_id,
                e.category_id,
                e.project_id,
                e.user_id,
                e.`current_date`,
                e.description,
                e.paid_amt,
                e.unpaid_amt,
                e.extra_amt,
                e.image,
                e.editedBy,
                e.payment_mode,
                e.created_at,
                e.updated_at,
                e.deleted_at,
                e.reason,
                e.labour_id,
                e.vendor_id,
                e.is_advance
            FROM `{$this->tempDatabase}`.expenses e
            WHERE e.user_id IN (SELECT id FROM users)
              AND e.category_id IN (SELECT id FROM categories)
              AND (e.main_category_id IS NULL OR e.main_category_id IN (SELECT id FROM main_categories))
              AND (e.project_id IS NULL OR e.project_id IN (SELECT id FROM projects))
              AND (e.labour_id IS NULL OR e.labour_id IN (SELECT id FROM labours))
              AND (e.vendor_id IS NULL OR e.vendor_id IN (SELECT id FROM vendors))
            ON DUPLICATE KEY UPDATE
                amount = VALUES(amount), paid_amt = VALUES(paid_amt), unpaid_amt = VALUES(unpaid_amt),
                extra_amt = VALUES(extra_amt), description = VALUES(description), editedBy = VALUES(editedBy),
                payment_mode = VALUES(payment_mode), deleted_at = VALUES(deleted_at), reason = VALUES(reason),
                updated_at = VALUES(updated_at)
        ");
    }

    private function importExpenseSettlements(): void
    {
        if (! $this->sourceTableExists('expenses_unpaid_date')) {
            return;
        }

        $this->runMapping('expenses_unpaid_date', "
            INSERT INTO expenses_unpaid_date (id, expense_id, user_id, paid_amount, `current_date`, `current_time`, notes, created_at, updated_at)
            SELECT
                id,
                expense_id,
                COALESCE((SELECT user_id FROM expenses e WHERE e.id = old_unpaid.expense_id), (SELECT MIN(id) FROM users)),
                COALESCE(unpaid_amt, 0),
                DATE(`current_date`),
                TIME(`current_date`),
                'Imported old unpaid history',
                created_at,
                updated_at
            FROM `{$this->tempDatabase}`.expenses_unpaid_date old_unpaid
            WHERE expense_id IN (SELECT id FROM expenses)
            ON DUPLICATE KEY UPDATE paid_amount = VALUES(paid_amount), notes = VALUES(notes), updated_at = VALUES(updated_at)
        ");
    }

    private function importAdvanceHistory(): void
    {
        if (! $this->sourceTableExists('advance_history')) {
            return;
        }

        $vendorColumn = Schema::hasColumn('advance_history', 'vendor_id') ? ', vendor_id' : '';
        $vendorSelect = Schema::hasColumn('advance_history', 'vendor_id') ? ', ah.vendor_id' : '';

        $this->runMapping('advance_history', "
            INSERT INTO advance_history (
                id, labour_id {$vendorColumn}, labour_expense_transaction_id, amount, entry_type,
                notes, user_id, `current_date`, `current_time`, created_at, updated_at
            )
            SELECT
                ah.id,
                IF(ah.labour_id IN (SELECT id FROM labours), ah.labour_id, NULL)
                {$vendorSelect},
                IF(ah.expense_id IN (SELECT id FROM expenses), ah.expense_id, NULL),
                ah.amount,
                'credit',
                'Imported old advance history',
                (SELECT MIN(id) FROM users),
                DATE(ah.date),
                TIME(ah.date),
                ah.created_at,
                ah.updated_at
            FROM `{$this->tempDatabase}`.advance_history ah
            WHERE (ah.labour_id IN (SELECT id FROM labours) OR ah.vendor_id IN (SELECT id FROM vendors))
            ON DUPLICATE KEY UPDATE amount = VALUES(amount), notes = VALUES(notes), updated_at = VALUES(updated_at)
        ");
    }

    private function importWallet(): void
    {
        if (! $this->sourceTableExists('wallet')) {
            return;
        }

        $this->copyLikeNamedTable('wallet', [
            'id', 'amount', 'user_id', 'client_id', 'project_id', 'current_date', 'description',
            'active_status', 'delete_status', 'payment_mode', 'created_at', 'updated_at', 'stage_id', 'transfer_type',
        ], "user_id IN (SELECT id FROM users)");
    }

    private function importTransfers(): void
    {
        if (! $this->sourceTableExists('transferdetails') || ! Schema::hasTable('transferdetails')) {
            return;
        }

        $mapping = [
            'id' => 'id',
            'amount' => 'amount',
            'employee_id' => $this->sourceColumnExists('transferdetails', 'member_id')
                ? 'IF(member_id IN (SELECT id FROM users), member_id, NULL) as employee_id'
                : 'NULL as employee_id',
            'user_id' => 'user_id',
            'vendor_id' => $this->sourceColumnExists('transferdetails', 'vendor_id')
                ? 'IF(vendor_id IN (SELECT id FROM vendors), vendor_id, NULL) as vendor_id'
                : 'NULL as vendor_id',
            'current_date' => 'DATE(`current_date`) as `current_date`',
            'current_time' => 'TIME(`current_date`) as `current_time`',
            'description' => 'description',
            'payment_mode' => 'CAST(payment_mode AS CHAR) as payment_mode',
            'active_status' => '1 as active_status',
            'delete_status' => '0 as delete_status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'transfer_type' => $this->sourceColumnExists('transferdetails', 'vendor_id')
                ? "CASE WHEN vendor_id IS NOT NULL AND vendor_id > 0 THEN 'vendor' ELSE 'employee' END as transfer_type"
                : "'employee' as transfer_type",
        ];

        $targetColumns = array_values(array_filter(
            array_keys($mapping),
            fn(string $column): bool => Schema::hasColumn('transferdetails', $column)
        ));

        $selects = array_map(fn(string $column): string => $mapping[$column], $targetColumns);

        $this->runMapping('transferdetails', sprintf(
            'INSERT INTO transferdetails (%s) SELECT %s FROM `%s`.transferdetails WHERE user_id IN (SELECT id FROM users) ON DUPLICATE KEY UPDATE amount = VALUES(amount), description = VALUES(description), updated_at = VALUES(updated_at)',
            implode(', ', array_map(fn(string $column): string => $this->quoteColumn($column), $targetColumns)),
            implode(', ', $selects),
            $this->tempDatabase
        ));
    }

    private function copyLikeNamedTable(string $table, array $columns, string $where = '1=1'): void
    {
        if (! Schema::hasTable($table) || ! $this->sourceTableExists($table)) {
            return;
        }

        $columns = array_values(array_filter($columns, fn(string $column): bool => Schema::hasColumn($table, $column)));
        if ($columns === []) {
            return;
        }

        $quotedColumns = array_map(fn(string $column): string => $this->quoteColumn($column), $columns);
        $updates = implode(', ', array_map(
            fn(string $column): string => $this->quoteColumn($column) . ' = VALUES(' . $this->quoteColumn($column) . ')',
            array_filter($columns, fn($column) => $column !== 'id')
        ));

        $this->runMapping($table, sprintf(
            'INSERT INTO %s (%s) SELECT %s FROM `%s`.%s WHERE %s ON DUPLICATE KEY UPDATE %s',
            $this->quoteColumn($table),
            implode(', ', $quotedColumns),
            implode(', ', $quotedColumns),
            $this->tempDatabase,
            $this->quoteColumn($table),
            $where,
            $updates ?: 'id = id'
        ));
    }

    private function quoteColumn(string $column): string
    {
        return '`' . str_replace('`', '``', $column) . '`';
    }

    private function runPostImportMaintenance(): void
    {
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);

        if (class_exists(\App\Console\Commands\RecalculateBalanceFields::class)) {
            Artisan::call('balances:recalculate');
        }
    }

    private function runMapping(string $label, string $sql): void
    {
        $this->line("Importing {$label}...");
        DB::statement($sql);
    }

    private function sourceTableExists(string $table): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) as aggregate FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->tempDatabase, $table]
        )->aggregate > 0;
    }

    private function sourceColumnExists(string $table, string $column): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) as aggregate FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$this->tempDatabase, $table, $column]
        )->aggregate > 0;
    }

    private function sourceCount(string $table): int
    {
        return (int) DB::selectOne("SELECT COUNT(*) as aggregate FROM `{$this->tempDatabase}`.`{$table}`")->aggregate;
    }

    private function sanitizeIdentifier(string $identifier): string
    {
        $identifier = preg_replace('/[^A-Za-z0-9_]/', '_', $identifier) ?: 'old_import';

        return substr($identifier, 0, 60);
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                    if ($i < $length) {
                        $buffer .= $sql[$i];
                    }
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
