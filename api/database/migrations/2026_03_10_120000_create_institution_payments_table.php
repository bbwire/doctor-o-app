<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('type', 50)->default('subscription'); // subscription, registration_fee, etc.
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_payments');
    }
};
