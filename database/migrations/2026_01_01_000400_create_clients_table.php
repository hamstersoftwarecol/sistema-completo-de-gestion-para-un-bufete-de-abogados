<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('persona');
            $table->string('name');
            $table->string('document_type', 20)->nullable();
            $table->string('document_number', 40)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('alt_phone', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('occupation')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
