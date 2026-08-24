<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The nurse and the clinic administrator now BOTH register the full patient
     * profile (fill_record_sheet) and can route a case to a doctor or a
     * mammographer from the registration form itself (assign_doctors).
     *
     * Backfills existing users; on a fresh migrate-then-seed database the roles
     * don't exist yet and the seeders grant these from Rbac::ROLE_DEFAULTS.
     */
    private const GRANTS = [
        'clinic_admin' => ['fill_record_sheet'],
        'nurse'        => ['assign_doctors'],
    ];

    public function up(): void
    {
        foreach (self::GRANTS as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $user) => $user->givePermissionTo($permissions));
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $user) => $user->revokePermissionTo($permissions));
        }
    }
};
