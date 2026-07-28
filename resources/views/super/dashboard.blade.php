@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_dashboard'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        {{-- Filters --}}
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px;">
            @php($fs = 'padding:8px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13px;background:#fff;')
            <select name="emirate" style="{{ $fs }}"><option value="">{{ __('pc.emirate') }}: {{ __('pc.status') === 'Status' ? 'All' : 'الكل' }}</option>@foreach ($emirates as $v=>$l)<option value="{{ $v }}" @selected(($filters['emirate'] ?? '')===$v)>{{ $l }}</option>@endforeach</select>
            <select name="type" style="{{ $fs }}"><option value="">{{ __('pc.type_col') }}</option>@foreach ($types as $v=>$l)<option value="{{ $v }}" @selected(($filters['type'] ?? '')===$v)>{{ $l }}</option>@endforeach</select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" style="{{ $fs }}" />
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" style="{{ $fs }}" />
            <button type="submit" style="cursor:pointer;background:#E6017E;color:#fff;border:none;font-weight:600;font-size:13px;padding:9px 16px;border-radius:9px;">{{ __('pc.view') }}</button>
            <div style="margin-inline-start:auto;display:flex;gap:8px;align-items:center;">
                <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#2E7D32;background:#E4F4EF;padding:7px 13px;border-radius:999px;"><span class="pc-pulse" style="width:7px;height:7px;border-radius:50%;background:#2E7D32;display:inline-block;"></span>{{ __('pc.live_update') }}</span>
                <a href="{{ route('super.export.csv', $filters) }}" style="cursor:pointer;font-size:12.5px;font-weight:600;color:#2E7D32;background:#fff;border:1px solid #C7E6D4;padding:7px 13px;border-radius:9px;text-decoration:none;">↓ {{ __('pc.export_excel') }}</a>
                <a href="{{ route('super.export.pdf', $filters) }}" style="cursor:pointer;font-size:12.5px;font-weight:600;color:#C62828;background:#fff;border:1px solid #F1C4C4;padding:7px 13px;border-radius:9px;text-decoration:none;">↓ {{ __('pc.export_pdf') }}</a>
            </div>
        </form>

        <div style="display:grid;grid-template-columns:repeat(5, 1fr);gap:14px;margin-bottom:18px;">
            @foreach ($dashStats as $ds)
                <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:18px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;"><div style="width:30px;height:30px;border-radius:9px;background:{{ $ds['tint'] }};display:flex;align-items:center;justify-content:center;font-size:15px;">{{ $ds['icon'] }}</div></div>
                    <div style="font-size:27px;font-weight:700;color:{{ $ds['color'] }};letter-spacing:-.01em;">{{ $ds['val'] }}</div>
                    <div style="font-size:12px;color:#9A8F97;margin-top:2px;">{{ $ds['label'] }}</div>
                    <div style="font-size:11.5px;color:{{ $ds['subColor'] }};margin-top:3px;font-weight:600;">{{ $ds['sub'] }}</div>
                </div>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:18px;">
            {{-- By emirate --}}
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:22px 24px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;">{{ __('pc.by_emirate') }}</h3>
                @foreach ($byEmirate as $e)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:11px;">
                        <div style="width:120px;font-size:12.5px;color:#6B4257;font-weight:500;">{{ $e['name'] }}</div>
                        <div style="flex:1;height:12px;background:#F3E7EE;border-radius:6px;overflow:hidden;"><div style="width:{{ $e['pct'] }};height:100%;background:{{ $e['color'] }};border-radius:6px;"></div></div>
                        <div style="width:52px;text-align:end;font-size:12.5px;font-weight:700;color:#2A2230;">{{ $e['val'] }}</div>
                    </div>
                @endforeach
            </div>
            {{-- By result donut --}}
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:22px 24px;box-shadow:0 3px 14px rgba(120,60,90,.05);display:flex;flex-direction:column;align-items:center;">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;align-self:flex-start;">{{ __('pc.by_result') }}</h3>
                @php($np = $stats['normalPct'] ?? 0)
                <div style="width:160px;height:160px;border-radius:50%;background:conic-gradient(#2E7D32 0 {{ $np }}%, #E6017E {{ $np }}% 100%);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
                    <div style="width:108px;height:108px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:26px;font-weight:700;">{{ $np }}%</div><div style="font-size:11px;color:#9A8F97;">{{ __('pc.normal') }}</div></div>
                </div>
                <div style="display:flex;gap:20px;">
                    <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;"><span style="width:11px;height:11px;border-radius:3px;background:#2E7D32;"></span>{{ __('pc.normal') }} · {{ number_format($stats['normal'] ?? 0) }}</div>
                    <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;"><span style="width:11px;height:11px;border-radius:3px;background:#E6017E;"></span>{{ __('pc.abnormal') }} · {{ number_format($stats['abnormal'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        {{-- Throughput --}}
        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:22px 24px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <h3 style="margin:0 0 16px;font-size:15px;font-weight:700;">{{ __('pc.clinic_throughput') }}</h3>
            @foreach ($clinics as $c)
                <div style="display:flex;align-items:center;gap:14px;padding:11px 0;border-bottom:1px solid #F3E7EE;">
                    <div style="flex:1;font-size:13.5px;font-weight:600;">{{ $c['name'] }}</div>
                    <span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $c['tC'] }};background:{{ $c['tBg'] }};">{{ $c['tLabel'] }}</span>
                    <div style="width:110px;font-size:12.5px;color:#9A8F97;">{{ $c['em'] }}</div>
                    <div style="width:90px;text-align:end;font-size:14px;font-weight:700;color:#E6017E;">{{ $c['today'] }} <span style="font-size:11px;color:#B7A9B2;font-weight:500;">{{ __('pc.today_col') }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
</x-staff-shell>
@endsection
