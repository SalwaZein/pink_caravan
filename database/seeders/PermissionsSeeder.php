<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /** Create every permission (web guard). Runs before UsersSeeder. */
    public function run(): void
    {
        foreach (Rbac::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Refresh the cache so later seeders (UsersSeeder) can resolve these by name.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
