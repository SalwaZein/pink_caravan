<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Demo staff mirroring the design prototype, each with a role and clinic
     * assignment(s). Runs after RolesSeeder + ClinicSeeder.
     * All demo accounts use the password "password".
     */
    public function run(): void
    {
        // Dubai (DXB-MOB-01) is set up as a full clinic — admin + nurse + doctor —
        // so the register → assign → examine handoff can be demoed within one clinic.
        $staff = [
            ['name' => 'Anish Mathew',     'email' => 'anish@focp.ae',    'role' => 'super_admin',  'clinics' => []],
            ['name' => 'Mariam Saeed',     'email' => 'mariam.s@focp.ae', 'role' => 'clinic_admin', 'clinics' => ['DXB-MOB-01']],
            ['name' => 'Dr. Layla Hassan', 'email' => 'l.hassan@focp.ae', 'role' => 'doctor',       'clinics' => ['SHJ-FIX-01', 'DXB-MOB-01']],
            ['name' => 'Dr. Omar Farid',   'email' => 'o.farid@focp.ae',  'role' => 'doctor',       'clinics' => ['AUH-MOB-01']],
            ['name' => 'Sara Al Nuaimi',   'email' => 's.nuaimi@focp.ae', 'role' => 'nurse',        'clinics' => ['DXB-MOB-01']],
            ['name' => 'Hind Al Ali',      'email' => 'h.alali@focp.ae',  'role' => 'nurse',        'clinics' => ['SHJ-FIX-01']],
            ['name' => 'Noura Khalid',     'email' => 'n.khalid@focp.ae', 'role' => 'mammographer', 'clinics' => ['DXB-MOB-01']],
        ];

        foreach ($staff as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')],
            );

            $user->syncRoles([$data['role']]);
            $user->syncPermissions(Rbac::defaultsFor($data['role']));

            $clinicIds = Clinic::whereIn('code', $data['clinics'])->pluck('id');
            $user->clinics()->sync($clinicIds);
        }
    }
}
