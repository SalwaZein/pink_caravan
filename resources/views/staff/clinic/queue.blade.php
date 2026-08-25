@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_clinic_queue'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:14px;margin-bottom:18px;">
            @foreach ($miniStats as $ms)
                <div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:16px 18px;box-shadow:0 3px 14px rgba(120,60,90,.05);"><div style="font-size:12px;color:#9A8F97;font-weight:600;">{{ $ms['label'] }}</div><div style="font-size:26px;font-weight:700;margin-top:4px;color:{{ $ms['color'] }};">{{ $ms['val'] }}</div></div>
            @endforeach
        </div>
        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div style="display:grid;grid-template-columns:150px 1.4fr 60px 130px 130px 160px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;"><div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.age') }}</div><div>{{ __('pc.status') }}</div><div>{{ __('pc.doctor_col') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div></div>
            @foreach ($queue as $p)
                <div style="display:grid;grid-template-columns:150px 1.4fr 60px 130px 130px 160px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                    <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $p['ref'] }}</div>
                    <div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:50%;background:{{ $p['tint'] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">{{ $p['init'] }}</div><span style="font-weight:600;">{{ $p['name'] }}</span></div>
                    <div>{{ $p['age'] }}</div>
                    <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $p['stC'] }};background:{{ $p['stBg'] }};">{{ $p['stLabel'] }}</span></div>
                    <div style="font-size:12.5px;color:#6B6472;">{{ $p['doc'] }}</div>
                    <div style="text-align:end;display:flex;gap:8px;justify-content:flex-end;">
                        <a href="{{ route('clinic.record.show', $p['id']) }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#6B4257;padding:6px 12px;border:1px solid #E3D2DC;border-radius:8px;text-decoration:none;">{{ __('pc.view') }}</a>
                        @unless ($p['locked'])
                            <a href="{{ $p['openUrl'] }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#E6017E;padding:6px 12px;border:1px solid #F6BFD9;border-radius:8px;text-decoration:none;">{{ __('pc.edit') }}</a>
                        @endunless
                        <a href="{{ url('/clinic/assign') }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#6B4257;padding:6px 12px;border:1px solid #E3D2DC;border-radius:8px;text-decoration:none;">{{ __('pc.assign') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-staff-shell>
@endsection
