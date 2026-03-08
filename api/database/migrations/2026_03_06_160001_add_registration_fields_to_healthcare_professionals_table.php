<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->date('registration_date')->nullable()->after('license_number');
            $table->string('regulatory_council')->nullable()->after('registration_date');
        });
    }

    public function down(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->dropColumn(['registration_date', 'regulatory_council']);
        });
    }
};
