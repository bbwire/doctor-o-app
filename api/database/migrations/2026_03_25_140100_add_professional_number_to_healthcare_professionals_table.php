<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table): void {
            $table->string('professional_number', 32)->nullable()->unique()->after('speciality');
        });
    }

    public function down(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table): void {
            $table->dropColumn('professional_number');
        });
    }
};

