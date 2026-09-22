<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('description')->nullable();
            $t->string('original_filename'); $t->string('stored_filename');
            $t->string('storage_path')->unique(); $t->string('mime_type'); $t->unsignedBigInteger('file_size');
            $t->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('department_id')->constrained()->restrictOnDelete();
            $t->enum('security_level',['general','department','confidential'])->index(); $t->timestamps();
            $t->index(['department_id','security_level']);
        });
        Schema::create('document_shares', function (Blueprint $t) {
            $t->id(); $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('shared_by')->constrained('users')->restrictOnDelete();
            $t->timestamps(); $t->unique(['document_id','user_id']);
        });
        Schema::create('favorites', function (Blueprint $t) {
            $t->id(); $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->timestamps();
            $t->unique(['document_id','user_id']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event')->index(); $t->string('target_type')->nullable(); $t->string('target_id')->nullable();
            $t->string('result'); $t->json('context')->nullable(); $t->timestamp('created_at')->useCurrent()->index();
        });
        Schema::create('lab_flags', function (Blueprint $t) {
            $t->string('id',3)->primary(); $t->boolean('enabled')->default(false); $t->timestamps();
        });
        if (!Schema::connection('lab_write')->hasTable('lab_search_documents')) Schema::connection('lab_write')->create('lab_search_documents', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('description'); $t->boolean('restricted')->default(false);
        });
        Schema::create('lab_uploads', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('original_filename'); $t->string('storage_path')->unique(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::connection('lab_write')->dropIfExists('lab_search_documents');
        foreach (['lab_uploads','lab_flags','audit_logs','favorites','document_shares','documents'] as $table) Schema::dropIfExists($table);
    }
};
