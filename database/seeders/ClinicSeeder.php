<?php

namespace Database\Seeders;

use App\Enums\ClinicType;
use App\Enums\Emirate;
use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        // Mirrors the five demo clinics in the Pink Caravan design prototype.
        $clinics = [
            ['name' => 'Fixed Clinic – Sharjah', 'code' => 'SHJ-FIX-01', 'type' => ClinicType::Fixed,  'emirate' => Emirate::Sharjah,  'daily_capacity' => 120, 'contact_person' => 'Dr. Layla Hassan', 'contact_phone' => '+971 6 000 0001', 'is_active' => true],
            ['name' => 'Mobile Clinic – Dubai',   'code' => 'DXB-MOB-01', 'type' => ClinicType::Mobile, 'emirate' => Emirate::Dubai,    'daily_capacity' => 90,  'contact_person' => 'Sara Al Nuaimi',   'contact_phone' => '+971 4 000 0002', 'is_active' => true],
            ['name' => 'Mini Clinic – Ajman',     'code' => 'AJM-MINI-01','type' => ClinicType::Mini,   'emirate' => Emirate::Ajman,    'daily_capacity' => 60,  'contact_person' => 'Mariam Saeed',     'contact_phone' => '+971 6 000 0003', 'is_active' => true],
            ['name' => 'Mobile Clinic – Al Ain',  'code' => 'AUH-MOB-01', 'type' => ClinicType::Mobile, 'emirate' => Emirate::AbuDhabi, 'daily_capacity' => 80,  'contact_person' => 'Dr. Omar Farid',   'contact_phone' => '+971 3 000 0004', 'is_active' => true],
            ['name' => 'Mini Clinic – Fujairah',  'code' => 'FUJ-MINI-01','type' => ClinicType::Mini,   'emirate' => Emirate::Fujairah, 'daily_capacity' => 60,  'contact_person' => 'Hind Al Ali',      'contact_phone' => '+971 9 000 0005', 'is_active' => false],
        ];

        foreach ($clinics as $data) {
            Clinic::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
