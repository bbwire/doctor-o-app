<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->string('consultation_number', 32)->nullable()->unique()->after('id');
            $table->string('referral_number', 32)->nullable()->unique()->after('consultation_number');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->dropColumn(['referral_number', 'consultation_number']);
        });
    }
};

