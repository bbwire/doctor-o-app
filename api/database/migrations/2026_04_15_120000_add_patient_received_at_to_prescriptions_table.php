<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up (): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->timestamp('patient_received_at')->nullable()->after('status');
        });
    }

    public function down (): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropColumn('patient_received_at');
        });
    }
};
