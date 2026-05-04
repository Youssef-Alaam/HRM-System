<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee documents — uploaded by HR per the locked 2026-04-30 design
 * (employees and managers cannot upload or even view their own;
 * Walid: HR holds the records, not employee-self-service).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Null = optional/uncategorized doc (extra cert, etc.). Set =
            // links to a row in the required-document matrix.
            $table->foreignId('document_type_id')
                ->nullable()
                ->constrained('document_types')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_bytes');
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // One row per (org, employee, type) — replacements soft-delete
            // the old row. The unique index is partial via the deleted_at
            // null check enforced at the application layer (MySQL doesn't
            // do partial unique indexes natively, so the service guards it).
            $table->index(['org_id', 'employee_id']);
            $table->index(['org_id', 'document_type_id']);
            $table->index(['org_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
