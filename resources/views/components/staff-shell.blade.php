@props(['role', 'route'])

@php
    $isRtl = app()->getLocale() === 'ar';

    // Per-role sidebar config, mirroring the prototype's navConf.
    $conf = [
        'nurse' => [
            'label' => __('pc.role_nurse_label'), 'user' => 'Sara Al Nuaimi', 'init' => 'SN',
            'tint' => '#16A6A6', 'clinic' => __('pc.clinic_nurse'),
            'items' => [
                ['key' => 'nurse/queue',    'icon' => '📋', 'label' => __('pc.nav_todays_queue')],
                ['key' => 'nurse/record',   'icon' => '📝', 'label' => __('pc.nav_record_sheet')],
                ['key' => 'nurse/patients', 'icon' => '👥', 'label' => __('pc.nav_my_patients')],
            ],
        ],
        'doctor' => [
            'label' => __('pc.role_doctor_label'), 'user' => 'Dr. Layla Hassan', 'init' => 'LH',
            'tint' => '#2A6FDB', 'clinic' => __('pc.clinic_doctor'),
            'items' => [
                ['key' => 'doctor/assigned',  'icon' => '🔬', 'label' => __('pc.nav_assigned')],
                ['key' => 'doctor/exam',      'icon' => '🩻', 'label' => __('pc.nav_clinical_exam')],
                ['key' => 'doctor/completed', 'icon' => '✅', 'label' => __('pc.nav_completed')],
            ],
        ],
        'mammographer' => [
            'label' => __('pc.role_mammo_label'), 'user' => 'Noura Khalid', 'init' => 'NK',
            'tint' => '#0E9F8E', 'clinic' => __('pc.clinic_nurse'),
            'items' => [
                ['key' => 'mammographer/queue', 'icon' => '🩻', 'label' => __('pc.nav_mammo_reports')],
            ],
        ],
        'clinic' => [
            'label' => __('pc.role_clinic_label'), 'user' => 'Mariam Saeed', 'init' => 'MS',
            'tint' => '#F7941E', 'clinic' => __('pc.clinic_clinic'),
            'items' => [
                ['key' => 'clinic/queue',    'icon' => '📋', 'label' => __('pc.nav_clinic_queue')],
                ['key' => 'clinic/register', 'icon' => '➕', 'label' => __('pc.nav_register_patient')],
                ['key' => 'clinic/assign',   'icon' => '🔀', 'label' => __('pc.nav_assign_doctors')],
                ['key' => 'clinic/reports',  'icon' => '📈', 'label' => __('pc.nav_clinic_reports')],
            ],
        ],
        'super' => [
            'label' => __('pc.role_super_label'), 'user' => 'Anish Mathew', 'init' => 'AM',
            'tint' => '#7E4CC4', 'clinic' => __('pc.clinic_super'),
            'items' => [], // super's nav is entirely permission-driven (see admin items below)
        ],
    ][$role];

    // Prefer the authenticated user's identity; fall back to the demo profile.
    $authUser   = auth()->user();
    $userName   = $authUser?->name ?? $conf['user'];
    $userInit   = $authUser?->initials() ?? $conf['init'];
    $userClinic = $authUser?->clinics->first()?->name ?? $conf['clinic'];

    // Real pending-count badges for the current user (null hides the badge).
    $badgeFor = function (string $key) use ($authUser) {
        if (! $authUser) return null;
        $cids = $authUser->clinicIds();
        $R = fn () => \App\Models\PatientHistoryRecord::query();
        $n = match ($key) {
            'doctor/assigned'     => $R()->where('assigned_doctor_id', $authUser->id)->whereIn('status', ['assigned', 'in_review'])->count(),
            'nurse/queue'         => $cids ? $R()->whereIn('clinic_id', $cids)->whereDate('created_at', today())->count() : 0,
            'clinic/queue'        => $cids ? $R()->whereIn('clinic_id', $cids)->count() : 0,
            'clinic/assign'       => $cids ? $R()->whereIn('clinic_id', $cids)->whereIn('status', ['submitted', 'returned'])->count() : 0,
            'mammographer/queue'  => $R()->where('assigned_role', 'mammographer')->where('mammographer_id', $authUser->id)->whereIn('status', ['assigned', 'in_review'])->count(),
            default               => 0,
        };
        return $n > 0 ? (string) $n : null;
    };

    // Administration links shown per permission — not tied to a single role.
    $adminItems = [];
    if ($authUser) {
        if ($authUser->can('view_dashboards')) $adminItems[] = ['key' => 'super/dashboard', 'icon' => '📊', 'label' => __('pc.nav_dashboard')];
        if ($authUser->can('manage_clinics'))  $adminItems[] = ['key' => 'super/clinics',   'icon' => '🏥', 'label' => __('pc.nav_clinics')];
        if ($authUser->can('review_bookings')) $adminItems[] = ['key' => 'super/bookings',   'icon' => '📅', 'label' => __('pc.nav_bookings')];
        if ($authUser->can('review_bookings')) $adminItems[] = ['key' => 'super/bookings/dashboard', 'icon' => '📈', 'label' => __('pc.nav_bookings_dashboard')];
        if ($authUser->can('manage_users'))    $adminItems[] = ['key' => 'super/users',     'icon' => '🔐', 'label' => __('pc.nav_users_roles')];
        if ($authUser->can('view_audit'))      $adminItems[] = ['key' => 'super/audit',     'icon' => '🧾', 'label' => __('pc.nav_audit_log')];
    }

    $allItems = array_merge($conf['items'], $adminItems);
    $current  = collect($allItems)->firstWhere('key', $route) ?? ($allItems[0] ?? ['label' => $conf['label']]);
    $marginStart = $isRtl ? 'margin-right:auto;' : 'margin-left:auto;';
@endphp

<div style="min-height:100vh;display:flex;">
    {{-- Sidebar --}}
    <div style="width:262px;flex-shrink:0;background:#2A1622;color:#fff;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;">
        <div style="padding:22px 22px 18px;border-bottom:1px solid rgba(255,255,255,.09);">
            <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:11px;text-decoration:none;color:#fff;">
                <img src="{{ asset('assets/ribbon-ring.png') }}" alt="" style="width:34px;height:34px;" />
                <div>
                    <div style="font-weight:700;font-size:15px;letter-spacing:-.01em;">Pink Caravan</div>
                    <div style="font-size:11px;color:#E79BC2;font-weight:500;">{{ __('pc.platform') }}</div>
                </div>
            </a>
        </div>

        <div class="pc-scroll" style="padding:14px;flex:1;overflow-y:auto;">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.14em;color:#A97C93;padding:6px 12px 8px;font-weight:600;">{{ $conf['label'] }}</div>
            @foreach ($conf['items'] as $n)
                @php($active = $n['key'] === $route)
                <a href="{{ url('/'.$n['key']) }}"
                   style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:11px;margin-bottom:3px;font-size:14px;font-weight:500;text-decoration:none;color:{{ $active ? '#fff' : '#E9CBD9' }};background:{{ $active ? 'linear-gradient(90deg,#E6017E,#C0116E)' : 'transparent' }};">
                    <span style="font-size:17px;width:20px;text-align:center;">{{ $n['icon'] }}</span>
                    <span>{{ $n['label'] }}</span>
                    @php($badge = $badgeFor($n['key']))
                    @if ($badge)
                        <span style="{{ $marginStart }}background:#E6017E;color:#fff;font-size:11px;font-weight:700;padding:1px 8px;border-radius:999px;">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach

            @if (!empty($adminItems))
                <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.14em;color:#A97C93;padding:16px 12px 8px;font-weight:600;">{{ __('pc.administration') }}</div>
                @foreach ($adminItems as $n)
                    @php($active = $n['key'] === $route)
                    <a href="{{ url('/'.$n['key']) }}"
                       style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:11px;margin-bottom:3px;font-size:14px;font-weight:500;text-decoration:none;color:{{ $active ? '#fff' : '#E9CBD9' }};background:{{ $active ? 'linear-gradient(90deg,#E6017E,#C0116E)' : 'transparent' }};">
                        <span style="font-size:17px;width:20px;text-align:center;">{{ $n['icon'] }}</span>
                        <span>{{ $n['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </div>

        <div style="padding:14px;border-top:1px solid rgba(255,255,255,.09);">
            <div style="display:flex;align-items:center;gap:11px;padding:6px 6px 12px;">
                <div style="width:36px;height:36px;border-radius:50%;background:{{ $conf['tint'] }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;">{{ $userInit }}</div>
                <div style="line-height:1.2;">
                    <div style="font-size:13.5px;font-weight:600;">{{ $userName }}</div>
                    <div style="font-size:11.5px;color:#A97C93;">{{ $userClinic }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="cursor:pointer;width:100%;text-align:center;font-size:13px;font-weight:600;color:#E79BC2;padding:9px;border-radius:9px;border:1px solid rgba(255,255,255,.12);background:transparent;">⎋ {{ __('pc.sign_out') }}</button>
            </form>
        </div>
    </div>

    {{-- Main --}}
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;background:#F4EEF1;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 30px;background:#fff;border-bottom:1px solid #EFE2EA;position:sticky;top:0;z-index:15;">
            <div>
                <div style="font-size:12px;color:#9A8F97;font-weight:500;">{{ $conf['label'] }}</div>
                <div style="font-size:21px;font-weight:700;letter-spacing:-.01em;">{{ $current['label'] }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;background:#F7EEF3;border-radius:10px;padding:8px 13px;font-size:13.5px;font-weight:600;color:#6B4257;"><span>📍</span>{{ $userClinic }}</div>
                <x-lang-toggle variant="staff" />
            </div>
        </div>

        <div class="pc-scroll" style="flex:1;overflow-y:auto;padding:28px 30px 60px;">
            {{ $slot }}
        </div>
    </div>
</div>
