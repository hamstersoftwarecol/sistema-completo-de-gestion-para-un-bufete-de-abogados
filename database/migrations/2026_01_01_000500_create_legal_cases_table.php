<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 80)->unique();
            $table->string('title');
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('case_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('case_status_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('judge')->nullable();
            $table->foreignId('lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assistant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('media');
            $table->date('filing_date')->nullable();
            $table->date('closed_at')->nullable();
            $table->text('description')->nullable();
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->string('fee_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role', 40);
            $table->string('document_number', 40)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('lawyer_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category', 40)->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('case_notes');
        Schema::dropIfExists('case_parties');
        Schema::dropIfExists('legal_cases');
    }
};
