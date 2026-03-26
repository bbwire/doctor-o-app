<?php

namespace App\Support;

final class IdSystem
{
    private function __construct()
    {
        // static-only
    }

    /**
     * Healthcare professional prefix derived from the admin-selected speciality.
     *
     * Supported:
     * - Dentist => DT
     * - Nurse => NR
     * - Everything else (General Doctor / Physician / Surgeon / etc.) => DR
     */
    public static function professionalPrefix(?string $speciality): string
    {
        $s = trim((string) ($speciality ?? ''));

        if ($s === 'Dentist') {
            return 'DT';
        }

        if ($s === 'Nurse') {
            return 'NR';
        }

        return 'DR';
    }

    /**
     * Institution prefix derived from admin institution type.
     */
    public static function institutionPrefix(?string $type): ?string
    {
        $t = trim((string) ($type ?? ''));

        return match ($t) {
            'clinic', 'hospital' => 'CL',
            'lab' => 'LB',
            'pharmacy', 'drugshop' => 'PH',
            'radiology_center' => 'IM',
            default => null,
        };
    }
}

