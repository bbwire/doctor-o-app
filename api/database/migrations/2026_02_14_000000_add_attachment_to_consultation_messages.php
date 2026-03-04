<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table): void {
            $table->string('attachment_url')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table): void {
            $table->dropColumn('attachment_url');
        });
    }
};
