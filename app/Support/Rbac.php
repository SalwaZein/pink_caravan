<?php

namespace App\Support;

/**
 * Central definition of the platform's permissions, how they are grouped in the UI,
 * and the default permission set for each role (used to pre-fill the user form and
 * to seed demo users). Roles themselves carry no permissions — capabilities are
 * granted per user (direct permissions), so unchecking one in the form truly removes it.
 */
class Rbac
{
    /** Permissions grouped by area. Values are permission names. */
    public const GROUPS = [
        'administration'   => ['manage_clinics', 'manage_users'],
        'service_bookings' => ['review_bookings', 'approve_bookings', 'mark_bookings_paid', 'complete_bookings'],
        'operations'       => ['register_patients', 'assign_doctors'],
        'clinical'         => ['fill_record_sheet', 'perform_clinical_exam', 'manage_mammograms'],
        'reporting'        => ['view_dashboards', 'view_audit'],
    ];

    /** Default capabilities per role (a starting template — fully editable per user). */
    public const ROLE_DEFAULTS = [
        'super_admin'  => ['manage_clinics', 'manage_users', 'review_bookings', 'approve_bookings', 'mark_bookings_paid', 'complete_bookings', 'register_patients', 'assign_doctors', 'fill_record_sheet', 'perform_clinical_exam', 'manage_mammograms', 'view_dashboards', 'view_audit'],
        'clinic_admin' => ['register_patients', 'assign_doctors', 'view_dashboards'],
        'nurse'        => ['register_patients', 'fill_record_sheet'],
        'doctor'       => ['perform_clinical_exam'],
        'mammographer' => ['manage_mammograms'],
    ];

    /** Flat list of every permission name. */
    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    /** Default permissions for a given role name. */
    public static function defaultsFor(?string $role): array
    {
        return self::ROLE_DEFAULTS[$role] ?? [];
    }
}
