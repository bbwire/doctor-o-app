<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->decimal('consultation_charge', 12, 2)->nullable()->after('regulatory_council');
        });
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->dropColumn('consultation_charges');
        });
    }

    public function down(): void
    {
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->json('consultation_charges')->nullable()->after('regulatory_council');
        });
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->dropColumn('consultation_charge');
        });
    }
};
