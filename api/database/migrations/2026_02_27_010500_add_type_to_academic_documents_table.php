<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_documents', function (Blueprint $table): void {
            $table->string('type', 64)->nullable()->after('healthcare_professional_id');
        });
    }

    public function down(): void
    {
        Schema::table('academic_documents', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};

