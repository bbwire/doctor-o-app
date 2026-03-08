<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('doctor_wallet_balance', 12, 2)->default(0)->after('wallet_balance');
        });

        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->json('consultation_charges')->nullable()->after('regulatory_council');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('doctor_wallet_balance');
        });
        Schema::table('healthcare_professionals', function (Blueprint $table) {
            $table->dropColumn('consultation_charges');
        });
    }
};
