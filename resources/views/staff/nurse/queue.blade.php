@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_todays_queue'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        @if (session('status'))
            <div style="margin-bottom:16px;display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:13px;font-weight:700;padding:10px 16px;border-radius:12px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span>{{ session('status') }}
            </div>
        @endif
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;">
            <div style="flex:1;max-width:340px;display:flex;align-items:center;gap:9px;background:#fff;border:1px solid #EAD9E2;border-radius:11px;padding:10px 14px;"><span style="color:#B7A9B2;">🔍</span><input placeholder="{{ __('pc.search') }}" style="border:none;outline:none;flex:1;font-size:14px;background:transparent;" /></div>
            <a href="{{ route('nurse.record') }}" role="button" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:600;font-size:14px;padding:11px 20px;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);text-decoration:none;">+ {{ __('pc.register_new') }}</a>
        </div>
        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div style="display:grid;grid-template-columns:150px 1.4fr 60px 120px 130px 100px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;">
                <div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.age') }}</div><div>{{ __('pc.status') }}</div><div>{{ __('pc.doctor_col') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div>
            </div>
            @forelse ($queue as $p)
                <div style="display:grid;grid-template-columns:150px 1.4fr 60px 120px 130px 100px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                    <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $p['ref'] }}</div>
                    <div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:50%;background:{{ $p['tint'] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">{{ $p['init'] }}</div><div><div style="font-weight:600;">{{ $p['name'] }}</div><div style="font-size:11.5px;color:#9A8F97;">{{ $p['emirate'] }} · {{ $p['time'] }}</div></div></div>
                    <div>{{ $p['age'] }}</div>
                    <div><span style="display:inline-block;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['stC'] }};background:{{ $p['stBg'] }};">{{ $p['stLabel'] }}</span></div>
                    <div style="font-size:12.5px;color:#6B6472;">{{ $p['doc'] }}</div>
                    <div style="text-align:end;"><a href="{{ $p['openUrl'] }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#E6017E;padding:6px 12px;border:1px solid #F6BFD9;border-radius:8px;text-decoration:none;">{{ $p['editable'] ? __('pc.continue_draft') : __('pc.open') }}</a></div>
                </div>
            @empty
                <div style="padding:34px 20px;text-align:center;color:#9A8F97;font-size:14px;grid-column:1 / -1;">{{ __('pc.no_records') }}</div>
            @endforelse
        </div>
    </div>
</x-staff-shell>
@endsection
