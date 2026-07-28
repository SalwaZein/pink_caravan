@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_clinic_reports'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:14px;margin-bottom:18px;">
            @foreach ($reportStats as $ms)
                <div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:18px 20px;box-shadow:0 3px 14px rgba(120,60,90,.05);"><div style="font-size:12px;color:#9A8F97;font-weight:600;">{{ $ms['label'] }}</div><div style="font-size:28px;font-weight:700;margin-top:4px;color:{{ $ms['color'] }};">{{ $ms['val'] }}</div><div style="font-size:11.5px;color:#B7A9B2;margin-top:2px;">{{ $ms['sub'] }}</div></div>
            @endforeach
        </div>
        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:22px 24px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;"><h3 style="margin:0;font-size:15px;font-weight:700;">{{ __('pc.daily_throughput') }}</h3><div style="display:flex;gap:8px;"><span role="button" style="cursor:pointer;font-size:12px;font-weight:600;color:#2E7D32;background:#E4F4EF;padding:6px 12px;border-radius:8px;">↓ {{ __('pc.export_excel') }}</span><span role="button" style="cursor:pointer;font-size:12px;font-weight:600;color:#C62828;background:#FBE4E4;padding:6px 12px;border-radius:8px;">↓ {{ __('pc.export_pdf') }}</span></div></div>
            <div style="display:flex;align-items:flex-end;gap:12px;height:180px;padding-top:10px;">
                @foreach ($weekBars as $wb)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;">
                        <div style="font-size:12px;font-weight:700;color:#6B4257;">{{ $wb['val'] }}</div>
                        <div style="width:100%;height:{{ $wb['h'] }};background:linear-gradient(180deg,#F48FB1,#E6017E);border-radius:8px 8px 0 0;"></div>
                        <div style="font-size:11.5px;color:#9A8F97;">{{ $wb['day'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-staff-shell>
@endsection
