<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_last_read_message_id')->nullable();
            $table->unsignedBigInteger('doctor_last_read_message_id')->nullable();
            $table->timestamp('patient_typing_at')->nullable();
            $table->timestamp('doctor_typing_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'patient_last_read_message_id',
                'doctor_last_read_message_id',
                'patient_typing_at',
                'doctor_typing_at',
            ]);
        });
    }
};
