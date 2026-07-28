<?php

namespace App\Support;

/**
 * Nationality options for patient registration. UAE + GCC are surfaced first
 * (most common at Pink Caravan clinics), followed by a broad alphabetical list.
 */
class Nationalities
{
    public static function all(): array
    {
        $priority = ['Emirati', 'Saudi', 'Qatari', 'Bahraini', 'Kuwaiti', 'Omani'];

        $others = [
            'Afghan', 'Algerian', 'American', 'Australian', 'Bangladeshi', 'British', 'Canadian',
            'Chinese', 'Egyptian', 'Eritrean', 'Ethiopian', 'Filipino', 'French', 'German', 'Ghanaian',
            'Indian', 'Indonesian', 'Iranian', 'Iraqi', 'Irish', 'Italian', 'Jordanian', 'Kenyan',
            'Lebanese', 'Malaysian', 'Moroccan', 'Nepalese', 'Nigerian', 'Pakistani', 'Palestinian',
            'Russian', 'South African', 'Spanish', 'Sri Lankan', 'Sudanese', 'Syrian', 'Tunisian',
            'Turkish', 'Ugandan', 'Ukrainian', 'Yemeni', 'Other',
        ];

        sort($others);

        return array_merge($priority, $others);
    }
}
