@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_register_patient'))

@php($inp = 'display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:14px;')
@php($lbl = 'font-size:12.5px;font-weight:600;color:#6B4257;')

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:720px;margin:0 auto;">
        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <p style="margin:0 0 20px;color:#6B6472;font-size:14px;">{{ __('pc.register_hint') }}</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <label style="{{ $lbl }}">{{ __('pc.full_name') }} *<input style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.dob') }}<input type="date" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.mobile1') }} *<input type="tel" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.emirate') }}<select style="{{ $inp }}background:#fff;"><option>Ajman</option><option>Dubai</option><option>Sharjah</option></select></label>
                <label style="grid-column:1 / -1;{{ $lbl }}">{{ __('pc.nurse_assign') }}<select style="{{ $inp }}background:#fff;"><option>Sara Al Nuaimi</option><option>Hind Al Ali</option></select></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:22px;">
                <a href="{{ url('/clinic/queue') }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;text-decoration:none;">{{ __('pc.back') }}</a>
                <a href="{{ url('/clinic/queue') }}" role="button" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:12px 26px;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);text-decoration:none;">{{ __('pc.register_add') }} →</a>
            </div>
        </div>
    </div>
</x-staff-shell>
@endsection
