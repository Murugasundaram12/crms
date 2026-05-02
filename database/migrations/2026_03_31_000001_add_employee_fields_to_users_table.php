<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'designation')) {
                $table->string('designation')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('Site Engineer')->after('designation');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'hourly_rate')) {
                $table->decimal('hourly_rate', 12, 2)->default(0)->after('address');
            }
            if (! Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('hourly_rate');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('hire_date');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('status');
            }
        });

        if (! Schema::hasTable('employees')) {
            return;
        }

        $employees = DB::table('employees')->orderBy('id')->get();

        foreach ($employees as $employee) {
            $existingUserId = DB::table('users')
                ->where('email', $employee->email)
                ->value('id');

            $payload = [
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'designation' => $employee->designation,
                'role' => $employee->role ?: 'Site Engineer',
                'address' => $employee->address,
                'hourly_rate' => $employee->hourly_rate ?? 0,
                'hire_date' => $employee->hire_date,
                'status' => $employee->status ?: 'active',
                'avatar' => $employee->avatar,
                'password' => $employee->password ?: Hash::make(Str::random(32)),
                'updated_at' => $employee->updated_at ?? now(),
            ];

            if ($existingUserId) {
                DB::table('users')->where('id', $existingUserId)->update($payload);
                continue;
            }

            DB::table('users')->insert($payload + [
                'email_verified_at' => null,
                'remember_token' => null,
                'created_at' => $employee->created_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'phone',
                'designation',
                'role',
                'address',
                'hourly_rate',
                'hire_date',
                'status',
                'avatar',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
