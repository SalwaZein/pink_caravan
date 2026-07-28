<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * RBAC groundwork for Phase 1b. Roles are created here; panel access and
     * clinic↔user assignment are wired up in a later phase.
     */
    public function run(): void
    {
        foreach (['super_admin', 'clinic_admin', 'nurse', 'doctor', 'mammographer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
