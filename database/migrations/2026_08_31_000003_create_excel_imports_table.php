<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('excel_imports', function (Blueprint $table) { $table->id(); $table->string('module'); $table->string('filename'); $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->default('queued'); $table->unsignedInteger('total_rows')->default(0); $table->unsignedInteger('imported_rows')->default(0); $table->unsignedInteger('skipped_rows')->default(0); $table->unsignedInteger('failed_rows')->default(0); $table->json('errors')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('excel_imports'); }
};
