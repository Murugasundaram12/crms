<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

return new class extends Migration
{
    use HasRoles;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->integer('user_type')->default(0);
            $table->integer('is_admin')->default(0);
            $table->integer('role_id')->default(0);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('type')->default(0);
            $table->string('confirm_password');
            $table->string('image');
            $table->integer('status')->default(0);
            $table->dateTime('message_checked_at');
            $table->integer('client_id')->default(0);
            $table->dateTime('notification_checked_at');
            $table->integer('is_primary_contact')->default(0);
            $table->string('job_title');
            $table->integer('disable_login')->default(0);
            $table->string('note');
            $table->string('address');
            $table->string('alternative_address');
            $table->string('phone');
            $table->string('alternative_phone');
            $table->date('dob');
            $table->string('ssn');
            $table->integer('gender');
            $table->string('sticky_note');
            $table->string('skype');
            $table->string('language');
            $table->integer('enable_web_notification')->default(0);
            $table->integer('enable_email_notification')->default(0);
            $table->integer('requested_account_removal')->default(0);
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->integer('wallet')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
