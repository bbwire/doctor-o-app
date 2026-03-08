<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_audit_logs')) {
            return;
        }

        $labels = [
            'app.name' => 'Application name',
            'app.timezone' => 'Application timezone',
            'consultations.slot_interval_minutes' => 'Consultation slot duration (minutes)',
            'consultations.availability_window_days' => 'How far ahead patients can book (days)',
            'consultations.minimum_action_lead_hours' => 'Minimum notice to book or cancel (hours)',
            'consultations.pricing.text' => 'Default price – text consultation',
            'consultations.pricing.audio' => 'Default price – audio consultation',
            'consultations.pricing.video' => 'Default price – video consultation',
            'consultations.pricing_by_speciality' => 'Consultation prices by speciality',
            'finance.platform_revenue_percentage' => 'Platform revenue share (%)',
        ];

        $rows = DB::table('setting_audit_logs')->orderBy('id')->get();

        foreach ($rows as $row) {
            $label = $labels[$row->key] ?? str_replace(['_', '.'], ' ', $row->key);
            $description = "Updated setting: {$label}";

            DB::table('activity_log')->insert([
                'user_id' => $row->user_id,
                'action' => 'settings.updated',
                'subject_type' => null,
                'subject_id' => null,
                'description' => $description,
                'properties' => json_encode([
                    'key' => $row->key,
                    'old_value' => $row->old_value,
                    'new_value' => $row->new_value,
                ]),
                'created_at' => $row->created_at,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')->where('action', 'settings.updated')->delete();
        }
    }
};
