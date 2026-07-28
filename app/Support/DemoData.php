<?php

namespace App\Support;

/**
 * Static demo data ported from the Pink Caravan design prototype (renderVals()).
 * Used to render the non-functional role screens faithfully in Phase 1.
 * These are illustrative fixtures only — later phases replace them with real records.
 */
class DemoData
{
    public const AV_TINTS = ['#16A6A6', '#2A6FDB', '#7E4CC4', '#F7941E', '#E6017E', '#43A047'];

    public static function statusMeta(): array
    {
        return [
            'registered' => ['label' => __('pc.registered'),      'c' => '#6B4257', 'bg' => '#F1E7ED'],
            'assessment' => ['label' => __('pc.in_assessment'),   'c' => '#B26A00', 'bg' => '#FCEBD6'],
            'awaiting'   => ['label' => __('pc.awaiting_doctor'), 'c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            'review'     => ['label' => __('pc.in_review'),       'c' => '#7E4CC4', 'bg' => '#EEE6FA'],
            'completed'  => ['label' => __('pc.completed'),       'c' => '#2E7D32', 'bg' => '#E4F4EF'],
        ];
    }

    public static function resultMeta(): array
    {
        return [
            'Normal'   => ['label' => __('pc.normal'),   'c' => '#2E7D32', 'bg' => '#E4F4EF'],
            'Abnormal' => ['label' => __('pc.abnormal'), 'c' => '#C62828', 'bg' => '#FBE4E4'],
            ''         => ['label' => '—',               'c' => '#B7A9B2', 'bg' => '#F4EEF1'],
        ];
    }

    /** Raw patient queue (8 rows). */
    public static function rawQueue(): array
    {
        return [
            ['ref' => 'PC-2026-018342', 'name' => 'Fatima A.', 'age' => 47, 'emirate' => 'Dubai',     'st' => 'completed',  'res' => 'Normal',   'doc' => 'Dr. Layla Hassan', 'time' => '09:12'],
            ['ref' => 'PC-2026-018351', 'name' => 'Noura K.',  'age' => 52, 'emirate' => 'Sharjah',   'st' => 'review',     'res' => '',         'doc' => 'Dr. Layla Hassan', 'time' => '09:40'],
            ['ref' => 'PC-2026-018358', 'name' => 'Aisha M.',  'age' => 38, 'emirate' => 'Dubai',     'st' => 'awaiting',   'res' => '',         'doc' => '—',                'time' => '10:05'],
            ['ref' => 'PC-2026-018360', 'name' => 'Mariam R.', 'age' => 44, 'emirate' => 'Ajman',     'st' => 'assessment', 'res' => '',         'doc' => '—',                'time' => '10:22'],
            ['ref' => 'PC-2026-018365', 'name' => 'Hessa S.',  'age' => 41, 'emirate' => 'Dubai',     'st' => 'completed',  'res' => 'Abnormal', 'doc' => 'Dr. Layla Hassan', 'time' => '10:48'],
            ['ref' => 'PC-2026-018370', 'name' => 'Latifa B.', 'age' => 29, 'emirate' => 'Sharjah',   'st' => 'registered', 'res' => '',         'doc' => '—',                'time' => '11:03'],
            ['ref' => 'PC-2026-018374', 'name' => 'Reem A.',   'age' => 55, 'emirate' => 'Abu Dhabi', 'st' => 'awaiting',   'res' => '',         'doc' => '—',                'time' => '11:20'],
            ['ref' => 'PC-2026-018379', 'name' => 'Salama H.', 'age' => 36, 'emirate' => 'Dubai',     'st' => 'review',     'res' => '',         'doc' => 'Dr. Layla Hassan', 'time' => '11:35'],
        ];
    }

    /** Decorate a raw queue row with initials, tint and badge colours. */
    public static function decorate(array $r, int $i): array
    {
        $status = self::statusMeta();
        $result = self::resultMeta();

        $init = collect(explode(' ', $r['name']))
            ->map(fn ($w) => mb_substr($w, 0, 1))
            ->join('');

        return array_merge($r, [
            'init'    => mb_substr($init, 0, 2),
            'tint'    => self::AV_TINTS[$i % count(self::AV_TINTS)],
            'stLabel' => $status[$r['st']]['label'], 'stC' => $status[$r['st']]['c'], 'stBg' => $status[$r['st']]['bg'],
            'resLabel'=> $result[$r['res']]['label'], 'resC' => $result[$r['res']]['c'], 'resBg' => $result[$r['res']]['bg'],
        ]);
    }

    public static function queue(): array
    {
        return collect(self::rawQueue())->map(fn ($r, $i) => self::decorate($r, $i))->all();
    }

    public static function doctorList(): array
    {
        return collect(self::rawQueue())
            ->filter(fn ($r) => in_array($r['st'], ['awaiting', 'review'], true))
            ->values()->map(fn ($r, $i) => self::decorate($r, $i))->all();
    }

    public static function completedList(): array
    {
        return collect(self::rawQueue())
            ->filter(fn ($r) => $r['st'] === 'completed')
            ->values()->map(fn ($r, $i) => self::decorate($r, $i))->all();
    }

    public static function assignList(): array
    {
        return collect(self::rawQueue())
            ->filter(fn ($r) => in_array($r['st'], ['awaiting', 'registered', 'assessment'], true))
            ->values()->map(fn ($r, $i) => self::decorate($r, $i))->all();
    }

    public static function symptoms(): array
    {
        return __('pc.symptom_list');
    }

    public static function signs(): array
    {
        return __('pc.sign_list');
    }

    public static function personalItems(): array
    {
        return [
            __('pc.lumpectomy'), __('pc.biopsy'), __('pc.hyperplasia'), __('pc.hrt'), __('pc.personal_bc'),
            __('pc.ovarian'), __('pc.fam_ovarian'), __('pc.fam_male_bc'), __('pc.implant'),
        ];
    }

    public static function famDegrees(): array
    {
        return [__('pc.deg1'), __('pc.deg2'), __('pc.deg3')];
    }

    public static function consentStatements(): array
    {
        return __('pc.consent_statements');
    }

    public static function dashStats(): array
    {
        return [
            ['icon' => '📋', 'tint' => '#FCE7F0', 'color' => '#E6017E', 'val' => '18,342', 'label' => __('pc.total_records'),  'sub' => '92% ' . __('pc.of_target'), 'subColor' => '#2E7D32'],
            ['icon' => '💗', 'tint' => '#E4F4EF', 'color' => '#16A6A6', 'val' => '16,802', 'label' => __('pc.screened_women'), 'sub' => '+1,240 ' . __('pc.this_week'), 'subColor' => '#2E7D32'],
            ['icon' => '⚠️', 'tint' => '#FBE4E4', 'color' => '#C62828', 'val' => '8.4%',   'label' => __('pc.abnormal_rate'),  'sub' => '1,540 ' . mb_strtolower(__('pc.abnormal')), 'subColor' => '#9A8F97'],
            ['icon' => '🔀', 'tint' => '#E3ECFB', 'color' => '#2A6FDB', 'val' => '1,204',  'label' => __('pc.referrals'),      'sub' => '846 ' . mb_strtolower(__('pc.completed')), 'subColor' => '#9A8F97'],
            ['icon' => '⏳', 'tint' => '#EEE6FA', 'color' => '#7E4CC4', 'val' => '63',      'label' => __('pc.pending_cases'),  'sub' => __('pc.in_follow_up'), 'subColor' => '#9A8F97'],
        ];
    }

    public static function byEmirate(): array
    {
        $stats = [['Abu Dhabi', 4120], ['Dubai', 5240], ['Sharjah', 3180], ['Ajman', 1740], ['Umm Al Quwain', 820], ['Ras Al Khaimah', 1980], ['Fujairah', 1262]];
        $max = max(array_map(fn ($e) => $e[1], $stats));

        return collect($stats)->map(fn ($e, $i) => [
            'name'  => $e[0],
            'val'   => number_format($e[1]),
            'pct'   => round($e[1] / $max * 100) . '%',
            'color' => self::AV_TINTS[$i % count(self::AV_TINTS)],
        ])->all();
    }

    /** Clinic throughput rows for the super dashboard + status badges. */
    public static function clinicsThroughput(): array
    {
        $typeMeta = [
            'fixed'  => ['label' => __('pc.fixed'),       'c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            'mobile' => ['label' => __('pc.mobile_type'), 'c' => '#16A6A6', 'bg' => '#DBF1EE'],
            'mini'   => ['label' => __('pc.mini'),        'c' => '#F7941E', 'bg' => '#FCEBD6'],
        ];
        $rows = [
            ['name' => 'Fixed Clinic – Sharjah', 'type' => 'fixed',  'em' => 'Sharjah',   'today' => 112],
            ['name' => 'Mobile Clinic – Dubai',  'type' => 'mobile', 'em' => 'Dubai',     'today' => 88],
            ['name' => 'Mini Clinic – Ajman',    'type' => 'mini',   'em' => 'Ajman',     'today' => 41],
            ['name' => 'Mobile Clinic – Al Ain', 'type' => 'mobile', 'em' => 'Abu Dhabi', 'today' => 64],
            ['name' => 'Mini Clinic – Fujairah', 'type' => 'mini',   'em' => 'Fujairah',  'today' => 0],
        ];

        return collect($rows)->map(fn ($c) => array_merge($c, [
            'tLabel' => $typeMeta[$c['type']]['label'],
            'tC'     => $typeMeta[$c['type']]['c'],
            'tBg'    => $typeMeta[$c['type']]['bg'],
        ]))->all();
    }

    public static function clinicMiniStats(): array
    {
        return [
            ['label' => __('pc.today_col'),       'val' => '41', 'color' => '#E6017E'],
            ['label' => __('pc.awaiting_doctor'), 'val' => '6',  'color' => '#2A6FDB'],
            ['label' => __('pc.in_review'),       'val' => '3',  'color' => '#7E4CC4'],
            ['label' => __('pc.completed'),       'val' => '32', 'color' => '#2E7D32'],
        ];
    }

    public static function clinicReportStats(): array
    {
        return [
            ['label' => __('pc.total_records'), 'val' => '1,284', 'color' => '#E6017E', 'sub' => __('pc.daily_throughput')],
            ['label' => __('pc.abnormal_rate'), 'val' => '7.9%',  'color' => '#C62828', 'sub' => '101 ' . mb_strtolower(__('pc.abnormal'))],
            ['label' => __('pc.referrals'),     'val' => '96',    'color' => '#2A6FDB', 'sub' => '12 ' . mb_strtolower(__('pc.pending'))],
            ['label' => __('pc.screened_women'),'val' => '1,183', 'color' => '#16A6A6', 'sub' => '92%'],
        ];
    }

    public static function weekBars(): array
    {
        $vals = [64, 88, 72, 41, 96, 58];
        $max = max($vals);
        $days = app()->getLocale() === 'ar'
            ? ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة']
            : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

        return collect($vals)->map(fn ($v, $i) => [
            'val' => $v, 'day' => $days[$i], 'h' => round($v / $max * 100) . '%',
        ])->all();
    }

    public static function users(): array
    {
        $roleMeta = [
            'Super Administrator'    => '#7E4CC4',
            'Clinic Administrator'   => '#F7941E',
            'Doctor'                 => '#2A6FDB',
            'Nurse / Medical Admin'  => '#16A6A6',
        ];
        $rows = [
            ['name' => 'Anish Mathew',     'role' => 'Super Administrator',   'clinic' => 'Campaign HQ',            'email' => 'anish@focp.ae',    'mfa' => true],
            ['name' => 'Mariam Saeed',     'role' => 'Clinic Administrator',  'clinic' => 'Mini Clinic – Ajman',    'email' => 'mariam.s@focp.ae', 'mfa' => false],
            ['name' => 'Dr. Layla Hassan', 'role' => 'Doctor',                'clinic' => 'Fixed Clinic – Sharjah', 'email' => 'l.hassan@focp.ae', 'mfa' => false],
            ['name' => 'Sara Al Nuaimi',   'role' => 'Nurse / Medical Admin', 'clinic' => 'Mobile Clinic – Dubai',  'email' => 's.nuaimi@focp.ae', 'mfa' => false],
            ['name' => 'Dr. Omar Farid',   'role' => 'Doctor',                'clinic' => 'Mobile Clinic – Al Ain', 'email' => 'o.farid@focp.ae',  'mfa' => false],
        ];

        return collect($rows)->map(function ($u) use ($roleMeta) {
            $init = collect(explode(' ', str_replace('Dr. ', '', $u['name'])))
                ->map(fn ($w) => mb_substr($w, 0, 1))->join('');

            return array_merge($u, [
                'init'     => mb_substr($init, 0, 2),
                'tint'     => $roleMeta[$u['role']] ?? '#E6017E',
                'mfaLabel' => $u['mfa'] ? '✓ ' . __('pc.mfa_on') : '—',
                'mfaC'     => $u['mfa'] ? '#2E7D32' : '#B7A9B2',
            ]);
        })->all();
    }

    public static function audit(): array
    {
        return [
            ['who' => 'Dr. Layla Hassan', 'act' => 'Submitted clinical examination',       'ent' => 'PC-2026-018342',        't' => '2 min ago',  'ic' => '🔬', 'tint' => '#2A6FDB'],
            ['who' => 'Sara Al Nuaimi',   'act' => 'Created patient record',               'ent' => 'PC-2026-018379',        't' => '11 min ago', 'ic' => '📝', 'tint' => '#16A6A6'],
            ['who' => 'System',           'act' => 'Report generated & sent (SMS + email)', 'ent' => 'PC-2026-018342',        't' => '14 min ago', 'ic' => '📤', 'tint' => '#E6017E'],
            ['who' => 'Mariam Saeed',     'act' => 'Assigned patient to Dr. Layla Hassan',  'ent' => 'PC-2026-018351',        't' => '22 min ago', 'ic' => '🔀', 'tint' => '#F7941E'],
            ['who' => 'Anish Mathew',     'act' => 'Exported campaign report (Excel)',      'ent' => 'Dashboard',             't' => '1 hr ago',   'ic' => '📊', 'tint' => '#7E4CC4'],
            ['who' => 'Anish Mathew',     'act' => 'Activated clinic',                     'ent' => 'Mobile Clinic – Al Ain', 't' => '3 hr ago',   'ic' => '🏥', 'tint' => '#43A047'],
        ];
    }
}
