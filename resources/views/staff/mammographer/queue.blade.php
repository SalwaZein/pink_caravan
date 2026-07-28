@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_mammo_reports'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    @if (session('status'))
        <div class="pc-anim" style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
    @endif

    <p style="margin:0 0 16px;font-size:13px;color:#6B6472;">{{ __('pc.mammo_queue_hint') }}</p>

    <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        <div style="display:grid;grid-template-columns:150px 1.4fr 110px 130px 110px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;">
            <div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.result_col') }}</div><div>{{ __('pc.status') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div>
        </div>
        @forelse ($rows as $p)
            <div style="display:grid;grid-template-columns:150px 1.4fr 110px 130px 110px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $p['ref'] }}</div>
                <div style="display:flex;align-items:center;gap:10px;"><div style="width:30px;height:30px;border-radius:50%;background:{{ $p['tint'] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11.5px;">{{ $p['init'] }}</div><span style="font-weight:600;">{{ $p['name'] }}</span></div>
                <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['resC'] }};background:{{ $p['resBg'] }};">{{ $p['resLabel'] }}</span></div>
                <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['stC'] }};background:{{ $p['stBg'] }};">{{ $p['stLabel'] }}</span></div>
                <div style="text-align:end;"><a href="{{ $p['openUrl'] }}" style="text-decoration:none;font-size:12.5px;font-weight:700;color:#E6017E;">{{ __('pc.open_manage') }} →</a></div>
            </div>
        @empty
            <div style="padding:26px 20px;text-align:center;font-size:13.5px;color:#9A8F97;">{{ __('pc.no_completed_records') }}</div>
        @endforelse
    </div>
</x-staff-shell>
@endsection
