<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use App\Support\DemoStaff;
use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Demo staff mirroring the design prototype, each with a role and clinic
     * assignment(s). Runs after RolesSeeder + ClinicSeeder.
     *
     * The account list itself lives in App\Support\DemoStaff so the login
     * page's "demo accounts" panel renders exactly what was seeded.
     */
    public function run(): void
    {
        foreach (DemoStaff::all() as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make(DemoStaff::PASSWORD)],
            );

            $user->syncRoles([$data['role']]);
            $user->syncPermissions(Rbac::defaultsFor($data['role']));

            $clinicIds = Clinic::whereIn('code', $data['clinics'])->pluck('id');
            $user->clinics()->sync($clinicIds);
        }
    }
}
