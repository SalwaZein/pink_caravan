<?php

namespace App\Support;

/**
 * The demo staff accounts, in one place.
 *
 * Both `UsersSeeder` (which creates them) and the login page's "demo accounts"
 * panel read this list, so the two can never drift apart — the panel used to be
 * a hand-maintained string and had fallen four accounts behind.
 *
 * Listed in workflow order: who runs the campaign, who registers the patient,
 * who examines her, who reports, who works the door.
 */
class DemoStaff
{
    /** Shared password for every demo account (development / demo builds only). */
    public const PASSWORD = 'password';

    /**
     * @var list<array{name:string, email:string, role:string, clinics:list<string>}>
     *
     * Dubai (DXB-MOB-01) is deliberately staffed end to end — admin, nurse,
     * doctor, mammographer, radiologist and volunteer — so the whole handoff
     * demos inside one clinic.
     */
    public const ACCOUNTS = [
        ['name' => 'Anish Mathew',     'email' => 'anish@focp.ae',    'role' => 'super_admin',  'clinics' => []],
        ['name' => 'Mariam Saeed',     'email' => 'mariam.s@focp.ae', 'role' => 'clinic_admin', 'clinics' => ['DXB-MOB-01']],
        ['name' => 'Sara Al Nuaimi',   'email' => 's.nuaimi@focp.ae', 'role' => 'nurse',        'clinics' => ['DXB-MOB-01']],
        ['name' => 'Hind Al Ali',      'email' => 'h.alali@focp.ae',  'role' => 'nurse',        'clinics' => ['SHJ-FIX-01']],
        ['name' => 'Dr. Layla Hassan', 'email' => 'l.hassan@focp.ae', 'role' => 'doctor',       'clinics' => ['SHJ-FIX-01', 'DXB-MOB-01']],
        ['name' => 'Dr. Omar Farid',   'email' => 'o.farid@focp.ae',  'role' => 'doctor',       'clinics' => ['AUH-MOB-01']],
        ['name' => 'Noura Khalid',     'email' => 'n.khalid@focp.ae', 'role' => 'mammographer', 'clinics' => ['DXB-MOB-01']],
        ['name' => 'Dr. Huda Al Marri','email' => 'h.marri@focp.ae',  'role' => 'radiologist',  'clinics' => ['DXB-MOB-01']],
        ['name' => 'Aisha Rahman',     'email' => 'a.rahman@focp.ae', 'role' => 'volunteer',    'clinics' => ['DXB-MOB-01']],
    ];

    /** Every demo account, in the order above. */
    public static function all(): array
    {
        return self::ACCOUNTS;
    }
}
