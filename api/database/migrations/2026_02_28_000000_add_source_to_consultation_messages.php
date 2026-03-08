<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table): void {
            $table->string('source', 32)->default('user')->after('attachment_url');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_messages', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
