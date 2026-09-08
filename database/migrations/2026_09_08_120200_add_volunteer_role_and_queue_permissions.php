<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Business feedback #5 + #7: a dedicated volunteer role for the WhatsApp
     * token desk, and two new capabilities — running the visitor queue and
     * recording mammography findings.
     *
     * Backfills existing users; a fresh migrate-then-seed database gets these
     * from Rbac::ROLE_DEFAULTS via the seeders instead (hence the role guards).
     */
    private const PERMISSIONS = ['manage_queue', 'record_mammogram_findings'];

    private const GRANTS = [
        'super_admin'  => ['manage_queue', 'record_mammogram_findings'],
        'clinic_admin' => ['manage_queue'],
        'mammographer' => ['record_mammogram_findings'],
    ];

    public function up(): void
    {
        Role::findOrCreate('volunteer', 'web');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // The volunteer role exists only to run the queue desk.
        User::role('volunteer')->get()->each(fn (User $u) => $u->givePermissionTo('manage_queue'));

        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $u) => $u->givePermissionTo($permissions));
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $role => $permissions) {
            if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                continue;
            }

            User::role($role)->get()->each(fn (User $u) => $u->revokePermissionTo($permissions));
        }
    }
};
