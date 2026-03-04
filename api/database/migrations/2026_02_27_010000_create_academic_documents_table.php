<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('healthcare_professional_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 255)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_documents');
    }
};

