<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_number_sequences', function (Blueprint $table): void {
            $table->string('prefix', 8);
            $table->string('year_short', 2);
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->primary(['prefix', 'year_short']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_number_sequences');
    }
};

