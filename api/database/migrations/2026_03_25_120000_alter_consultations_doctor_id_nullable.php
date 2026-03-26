<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Needed for "waiting room" flow where a consultation may be created
        // before a doctor is assigned.
        //
        // Tests run against SQLite, and SQLite doesn't support "ALTER TABLE ... MODIFY",
        // so this migration must be a no-op there.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE consultations MODIFY doctor_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE consultations MODIFY doctor_id BIGINT UNSIGNED NOT NULL');
    }
};

