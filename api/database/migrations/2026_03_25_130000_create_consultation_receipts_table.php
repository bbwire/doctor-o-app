<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_settlement_id')->constrained('consultation_settlements')->cascadeOnDelete();
            $table->string('patient_email')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->string('file_path')->nullable(); // storage/app path (relative to disk)
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique('consultation_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_receipts');
    }
};

