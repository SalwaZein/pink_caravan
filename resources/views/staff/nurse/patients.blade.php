@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_my_patients'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim pc-tbl-wrap" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        <div class="pc-tbl" style="display:grid;grid-template-columns:150px 1.4fr 120px 130px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;"><div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.status') }}</div><div>{{ __('pc.result_col') }}</div></div>
        @foreach ($queue as $p)
            <div class="pc-tbl" style="display:grid;grid-template-columns:150px 1.4fr 120px 130px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $p['ref'] }}</div>
                <div style="display:flex;align-items:center;gap:10px;"><div style="width:30px;height:30px;border-radius:50%;background:{{ $p['tint'] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11.5px;">{{ $p['init'] }}</div><span style="font-weight:600;">{{ $p['name'] }}</span></div>
                <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['stC'] }};background:{{ $p['stBg'] }};">{{ $p['stLabel'] }}</span></div>
                <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['resC'] }};background:{{ $p['resBg'] }};">{{ $p['resLabel'] }}</span></div>
            </div>
        @endforeach
    </div>
</x-staff-shell>
@endsection
