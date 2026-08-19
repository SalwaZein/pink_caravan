@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_assigned'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        <div class="pc-tbl-wrap" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div class="pc-tbl" style="display:grid;grid-template-columns:150px 1.4fr 60px 120px 100px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;"><div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.age') }}</div><div>{{ __('pc.status') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div></div>
            @foreach ($doctorList as $p)
                <div class="pc-tbl" style="display:grid;grid-template-columns:150px 1.4fr 60px 120px 100px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                    <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $p['ref'] }}</div>
                    <div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:50%;background:{{ $p['tint'] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">{{ $p['init'] }}</div><div><div style="font-weight:600;">{{ $p['name'] }}</div><div style="font-size:11.5px;color:#9A8F97;">{{ $p['emirate'] }} · {{ $p['time'] }}</div></div></div>
                    <div>{{ $p['age'] }}</div>
                    <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['stC'] }};background:{{ $p['stBg'] }};">{{ $p['stLabel'] }}</span></div>
                    <div style="text-align:end;"><a href="{{ $p['openUrl'] }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#fff;background:#E6017E;padding:7px 14px;border-radius:8px;text-decoration:none;">{{ __('pc.open') }}</a></div>
                </div>
            @endforeach
        </div>
    </div>
</x-staff-shell>
@endsection
