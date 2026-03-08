<?php

namespace App\Support;

class AdminPermission
{
    public const MANAGE_USERS = 'manage_users';

    public const MANAGE_INSTITUTIONS = 'manage_institutions';

    public const MANAGE_HEALTHCARE_PROFESSIONALS = 'manage_healthcare_professionals';

    public const MANAGE_CONSULTATIONS = 'manage_consultations';

    public const MANAGE_PRESCRIPTIONS = 'manage_prescriptions';

    public const MANAGE_SETTINGS = 'manage_settings';

    public const MANAGE_FINANCE = 'manage_finance';

    public const VIEW_NOTIFICATIONS = 'view_notifications';

    /**
     * All permission keys (for validation and UI).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MANAGE_USERS,
            self::MANAGE_INSTITUTIONS,
            self::MANAGE_HEALTHCARE_PROFESSIONALS,
            self::MANAGE_CONSULTATIONS,
            self::MANAGE_PRESCRIPTIONS,
            self::MANAGE_SETTINGS,
            self::MANAGE_FINANCE,
            self::VIEW_NOTIFICATIONS,
        ];
    }

    /**
     * Labels for UI.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MANAGE_USERS => 'Manage users',
            self::MANAGE_INSTITUTIONS => 'Manage institutions',
            self::MANAGE_HEALTHCARE_PROFESSIONALS => 'Manage healthcare professionals',
            self::MANAGE_CONSULTATIONS => 'Manage consultations',
            self::MANAGE_PRESCRIPTIONS => 'Manage prescriptions',
            self::MANAGE_SETTINGS => 'Manage settings',
            self::VIEW_NOTIFICATIONS => 'View notifications',
        ];
    }

    /**
     * Route prefix to permission mapping (for middleware).
     *
     * @return array<string, string>
     */
    public static function routePermissionMap(): array
    {
        return [
            'admin/users' => self::MANAGE_USERS,
            'admin/institutions' => self::MANAGE_INSTITUTIONS,
            'admin/healthcare-professionals' => self::MANAGE_HEALTHCARE_PROFESSIONALS,
            'admin/consultations' => self::MANAGE_CONSULTATIONS,
            'admin/prescriptions' => self::MANAGE_PRESCRIPTIONS,
            'admin/settings' => self::MANAGE_SETTINGS,
            'admin/finance' => self::MANAGE_FINANCE,
            'admin/notifications' => self::VIEW_NOTIFICATIONS,
        ];
    }
}
