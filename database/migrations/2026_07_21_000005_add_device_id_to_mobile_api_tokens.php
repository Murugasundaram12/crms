<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('mobile_api_tokens', 'device_id')) {
                $table->string('device_id')->nullable()->after('name');
                $table->index(['user_id', 'device_id', 'expires_at'], 'mobile_api_tokens_user_device_expires_idx');
            }
        });

        DB::table('mobile_api_tokens')
            ->whereNull('device_id')
            ->orderBy('id')
            ->get(['id', 'user_id', 'name'])
            ->each(function (object $token): void {
                $device = DB::table('employee_devices')
                    ->where('employee_id', $token->user_id)
                    ->where('device_name', $token->name)
                    ->orderByDesc('last_seen_at')
                    ->first(['device_id']);

                $device ??= DB::table('employee_devices')
                    ->where('employee_id', $token->user_id)
                    ->orderByDesc('last_seen_at')
                    ->first(['device_id']);

                if ($device?->device_id) {
                    DB::table('mobile_api_tokens')
                        ->where('id', $token->id)
                        ->update(['device_id' => $device->device_id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('mobile_api_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('mobile_api_tokens', 'device_id')) {
                $table->dropIndex('mobile_api_tokens_user_device_expires_idx');
                $table->dropColumn('device_id');
            }
        });
    }
};
