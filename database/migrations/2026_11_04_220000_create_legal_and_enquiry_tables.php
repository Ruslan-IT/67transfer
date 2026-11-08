<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('body');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['legal_document_id', 'version']);
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index();
            $table->string('status')->default('new')->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('comment')->nullable();
            $table->json('payload');
            $table->foreignId('consent_version_id')->nullable()->constrained('legal_document_versions')->nullOnDelete();
            $table->timestamp('consented_at')->nullable();
            $table->string('fingerprint', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }
};
