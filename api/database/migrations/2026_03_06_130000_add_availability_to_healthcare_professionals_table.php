<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table): void {
            $table->time('availability_start_time')->nullable()->after('bio');
            $table->time('availability_end_time')->nullable()->after('availability_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table): void {
            $table->dropColumn(['availability_start_time', 'availability_end_time']);
        });
    }
};

