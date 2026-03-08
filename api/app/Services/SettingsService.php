<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;

class SettingsService
{
    private const CONSULTATION_KEYS = [
        'slot_interval_minutes' => 'consultations.slot_interval_minutes',
        'availability_window_days' => 'consultations.availability_window_days',
        'minimum_action_lead_hours' => 'consultations.minimum_action_lead_hours',
        'pricing.text' => 'consultations.pricing.text',
        'pricing.audio' => 'consultations.pricing.audio',
        'pricing.video' => 'consultations.pricing.video',
        'pricing_by_speciality' => 'consultations.pricing_by_speciality',
        'platform_revenue_percentage' => 'finance.platform_revenue_percentage',
    ];

    private const APP_KEYS = [
        'name' => 'app.name',
        'timezone' => 'app.timezone',
    ];

    public function get(string $key): mixed
    {
        $stored = Setting::getValue($key);
        if ($stored !== null && $stored !== '') {
            return $this->castValue($key, $stored);
        }

        return config($key);
    }

    public function set(string $key, mixed $value): void
    {
        Setting::setValue($key, $value === null ? null : (string) $value);
    }

    public function getAppName(): string
    {
        return (string) $this->get('app.name');
    }

    public function getAppTimezone(): string
    {
        return (string) $this->get('app.timezone');
    }

    public function getConsultationSlotIntervalMinutes(): int
    {
        $v = (int) $this->get('consultations.slot_interval_minutes');

        return max(15, min($v ?: 60, 120));
    }

    public function getConsultationAvailabilityWindowDays(): int
    {
        $v = (int) $this->get('consultations.availability_window_days');

        return max(1, min($v ?: 14, 30));
    }

    public function getConsultationMinimumActionLeadHours(): int
    {
        $v = (int) $this->get('consultations.minimum_action_lead_hours');

        return max(1, min($v ?: 2, 72));
    }

    /**
     * @return array{text: float, audio: float, video: float}
     */
    public function getConsultationPricing(): array
    {
        return [
            'text' => (float) $this->get('consultations.pricing.text'),
            'audio' => (float) $this->get('consultations.pricing.audio'),
            'video' => (float) $this->get('consultations.pricing.video'),
        ];
    }

    /**
     * Default consultation charge per speciality (admin-configured).
     *
     * @return array<string, float>  speciality => amount
     */
    public function getPricingBySpeciality(): array
    {
        $stored = Setting::getValue('consultations.pricing_by_speciality');
        if ($stored === null || $stored === '') {
            return [];
        }
        $decoded = json_decode($stored, true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $k => $v) {
            if (is_string($k) && is_numeric($v)) {
                $out[$k] = (float) $v;
            }
        }
        return $out;
    }

    /**
     * Resolve consultation amount for a doctor: doctor's own charge if set, else speciality default.
     */
    public function getConsultationAmountForDoctor(?\App\Models\HealthcareProfessional $doctorProfile): float
    {
        if (! $doctorProfile) {
            return 0;
        }
        if ($doctorProfile->consultation_charge !== null && (float) $doctorProfile->consultation_charge >= 0) {
            return (float) $doctorProfile->consultation_charge;
        }
        $bySpeciality = $this->getPricingBySpeciality();
        $speciality = $doctorProfile->speciality;
        $amount = $speciality ? ($bySpeciality[$speciality] ?? 0) : 0;
        return max(0, (float) $amount);
    }

    public function getPlatformRevenuePercentage(): float
    {
        $v = (float) $this->get('finance.platform_revenue_percentage');
        return max(0, min(100, $v ?: 10));
    }

    /**
     * Return all settings for admin (effective values: DB override or config default).
     */
    public function getForAdmin(): array
    {
        return [
            'app' => [
                'name' => $this->getAppName(),
                'env' => config('app.env'),
                'timezone' => $this->getAppTimezone(),
            ],
            'consultations' => [
                'slot_interval_minutes' => $this->getConsultationSlotIntervalMinutes(),
                'availability_window_days' => $this->getConsultationAvailabilityWindowDays(),
                'minimum_action_lead_hours' => $this->getConsultationMinimumActionLeadHours(),
                'pricing' => $this->getConsultationPricing(),
                'pricing_by_speciality' => $this->getPricingBySpeciality(),
            ],
            'finance' => [
                'platform_revenue_percentage' => $this->getPlatformRevenuePercentage(),
            ],
        ];
    }

    /**
     * Update settings from admin payload. Only allowed keys are written.
     * Supports both nested (app.name) and flat (app.name as key) validated payloads via Arr::get/Arr::has.
     *
     * @param  array<string, mixed>  $payload
     * @param  \App\Models\User|null  $user  User making the change (for audit).
     */
    public function updateFromAdmin(array $payload, ?\App\Models\User $user = null): void
    {
        $pricingBySpeciality = Arr::get($payload, 'consultations.pricing_by_speciality');
        if (is_array($pricingBySpeciality)) {
            $cleaned = [];
            foreach ($pricingBySpeciality as $k => $v) {
                if (is_string($k) && is_numeric($v) && (float) $v >= 0) {
                    $cleaned[$k] = (float) $v;
                }
            }
            $key = 'consultations.pricing_by_speciality';
            $oldValue = Setting::getValue($key);
            $newValue = json_encode($cleaned);
            Setting::setValue($key, $newValue);
            if ($user) {
                $this->logSettingsAudit($user, $key, $oldValue, $newValue);
            }
        }

        $updates = [
            'app.name' => fn () => (string) Arr::get($payload, 'app.name', ''),
            'app.timezone' => fn () => (string) Arr::get($payload, 'app.timezone', ''),
            'consultations.slot_interval_minutes' => fn () => max(15, min((int) Arr::get($payload, 'consultations.slot_interval_minutes', 60), 120)),
            'consultations.availability_window_days' => fn () => max(1, min((int) Arr::get($payload, 'consultations.availability_window_days', 14), 30)),
            'consultations.minimum_action_lead_hours' => fn () => max(1, min((int) Arr::get($payload, 'consultations.minimum_action_lead_hours', 2), 72)),
            'consultations.pricing.text' => fn () => (float) Arr::get($payload, 'consultations.pricing.text', 0),
            'consultations.pricing.audio' => fn () => (float) Arr::get($payload, 'consultations.pricing.audio', 0),
            'consultations.pricing.video' => fn () => (float) Arr::get($payload, 'consultations.pricing.video', 0),
            'finance.platform_revenue_percentage' => fn () => max(0, min(100, (float) Arr::get($payload, 'finance.platform_revenue_percentage', 10))),
        ];

        foreach ($updates as $key => $getValue) {
            if (! Arr::has($payload, $key)) {
                continue;
            }
            $newValue = $getValue();
            if (in_array($key, ['app.name', 'app.timezone'], true)) {
                $newValue = $newValue === '' ? null : (string) $newValue;
            } else {
                $newValue = $newValue === null ? null : (string) $newValue;
            }
            $oldValue = Setting::getValue($key);
            $this->set($key, $newValue);
            if ($user) {
                $this->logSettingsAudit($user, $key, $oldValue, $newValue);
            }
        }
    }

    private function logSettingsAudit(\App\Models\User $user, string $key, ?string $oldValue, ?string $newValue): void
    {
        app(AuditLogService::class)->logSettingsUpdated($user, $key, $oldValue, $newValue);
    }

    private function castValue(string $key, string $stored): mixed
    {
        if (str_starts_with($key, 'consultations.pricing') || str_starts_with($key, 'consultations.slot_') || str_starts_with($key, 'consultations.availability_') || str_starts_with($key, 'consultations.minimum_') || str_starts_with($key, 'finance.')) {
            return is_numeric($stored) ? (float) $stored : $stored;
        }

        return $stored;
    }
}
