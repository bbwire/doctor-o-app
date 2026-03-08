<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * Human-readable labels for settings keys (used in activity description).
     */
    private const SETTINGS_KEY_LABELS = [
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

    /**
     * Log an activity for the audit trail.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ?User $user,
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => Str::limit($action, 64),
            'subject_type' => $subjectType ? Str::limit($subjectType, 128) : null,
            'subject_id' => $subjectId,
            'description' => Str::limit($description, 512),
            'properties' => $properties ?: null,
        ]);
    }

    public function logSettingsUpdated(User $user, string $key, ?string $oldValue, ?string $newValue): ActivityLog
    {
        $label = self::SETTINGS_KEY_LABELS[$key] ?? str_replace(['_', '.'], ' ', $key);

        return $this->log($user, 'settings.updated', "Updated setting: {$label}", null, null, [
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
