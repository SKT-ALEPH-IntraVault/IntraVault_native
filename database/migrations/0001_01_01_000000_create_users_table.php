<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('departments', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('code')->unique(); $t->text('description')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('employee_number')->unique(); $t->string('name'); $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable(); $t->string('password');
            $t->foreignId('department_id')->constrained()->restrictOnDelete();
            $t->enum('role', ['system_admin','department_admin','employee'])->default('employee');
            $t->enum('status', ['active','suspended'])->default('active'); $t->rememberToken(); $t->timestamps();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary(); $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address',45)->nullable(); $t->text('user_agent')->nullable();
            $t->longText('payload'); $t->integer('last_activity')->index();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sessions'); Schema::dropIfExists('users'); Schema::dropIfExists('departments');
    }
};
