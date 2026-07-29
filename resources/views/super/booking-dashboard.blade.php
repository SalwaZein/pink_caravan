@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_bookings_dashboard'))

@php
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $kpis = [
        ['icon'=>'📅','tint'=>'#FCE7F0','color'=>'#E6017E','val'=>number_format($stats['total']),        'label'=>__('pc.booking_kpi_total')],
        ['icon'=>'⏳','tint'=>'#EEE6FA','color'=>'#7E4CC4','val'=>number_format($stats['pending']),      'label'=>__('pc.booking_kpi_pending')],
        ['icon'=>'✅','tint'=>'#E4F4EF','color'=>'#2E7D32','val'=>number_format($stats['completed']),    'label'=>__('pc.booking_kpi_completed')],
        ['icon'=>'💰','tint'=>'#FCEBD6','color'=>'#B26A00','val'=>'AED '.number_format($stats['revenue']), 'label'=>__('pc.booking_kpi_revenue')],
        ['icon'=>'🎯','tint'=>'#E3ECFB','color'=>'#2A6FDB','val'=>$stats['completionRate'].'%',           'label'=>__('pc.booking_kpi_completion')],
    ];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        {{-- Filters --}}
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px;">
            @php($fs = 'padding:8px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13px;background:#fff;')
            <select name="status" style="{{ $fs }}">
                <option value="">{{ __('pc.booking_status_col') }}</option>
                @foreach ($statuses as $v=>$l)<option value="{{ $v }}" @selected(($filters['status'] ?? '')===$v)>{{ $l }}</option>@endforeach
            </select>
            <select name="service_type" style="{{ $fs }}">
                <option value="">{{ __('pc.services') }}</option>
                @foreach ($serviceTypes as $v=>$l)<option value="{{ $v }}" @selected(($filters['service_type'] ?? '')===$v)>{{ $l }}</option>@endforeach
            </select>
            <select name="emirate" style="{{ $fs }}">
                <option value="">{{ __('pc.emirate') }}</option>
                @foreach ($emirates as $v=>$l)<option value="{{ $v }}" @selected(($filters['emirate'] ?? '')===$v)>{{ $l }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" style="{{ $fs }}" />
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" style="{{ $fs }}" />
            <button type="submit" style="cursor:pointer;background:#E6017E;color:#fff;border:none;font-weight:600;font-size:13px;padding:9px 16px;border-radius:9px;">{{ __('pc.view') }}</button>
        </form>

        {{-- KPI cards --}}
        <div style="display:grid;grid-template-columns:repeat(5, 1fr);gap:14px;margin-bottom:18px;">
            @foreach ($kpis as $k)
                <div style="{{ $card }}padding:18px;">
                    <div style="width:30px;height:30px;border-radius:9px;background:{{ $k['tint'] }};display:flex;align-items:center;justify-content:center;font-size:15px;margin-bottom:8px;">{{ $k['icon'] }}</div>
                    <div style="font-size:25px;font-weight:700;color:{{ $k['color'] }};letter-spacing:-.01em;">{{ $k['val'] }}</div>
                    <div style="font-size:12px;color:#9A8F97;margin-top:2px;">{{ $k['label'] }}</div>
                </div>
            @endforeach
        </div>

        @if ($stats['total'] === 0)
            <div style="{{ $card }}padding:40px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.booking_dash_empty') }}</div>
        @else
        {{-- Pipeline funnel + status breakdown --}}
        <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;margin-bottom:18px;">
            <div style="{{ $card }}padding:22px 24px;">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;">{{ __('pc.booking_pipeline') }}</h3>
                @foreach ($stats['funnel'] as $f)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:13px;">
                        <div style="width:96px;font-size:12.5px;color:#6B4257;font-weight:600;">{{ $f['name'] }}</div>
                        <div style="flex:1;height:20px;background:#F3E7EE;border-radius:6px;overflow:hidden;"><div style="width:{{ $f['pct'] }};height:100%;background:{{ $f['color'] }};border-radius:6px;transition:width .4s;"></div></div>
                        <div style="width:44px;text-align:end;font-size:13px;font-weight:700;color:#2A2230;">{{ $f['val'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="{{ $card }}padding:22px 24px;">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;">{{ __('pc.booking_by_status') }}</h3>
                @foreach ($stats['byStatus'] as $s)
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <span style="width:10px;height:10px;border-radius:3px;background:{{ $s['color'] }};flex-shrink:0;"></span>
                        <div style="flex:1;font-size:12.5px;color:#453A44;">{{ $s['name'] }}</div>
                        <div style="font-size:12.5px;color:#9A8F97;">{{ $s['pct'] }}</div>
                        <div style="width:40px;text-align:end;font-size:13px;font-weight:700;">{{ $s['val'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- By service + by emirate --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="{{ $card }}padding:22px 24px;">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;">{{ __('pc.booking_by_service') }}</h3>
                @foreach ($stats['byService'] as $s)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:11px;">
                        <div style="width:150px;font-size:12.5px;color:#6B4257;font-weight:500;">{{ $s['name'] }}</div>
                        <div style="flex:1;height:12px;background:#F3E7EE;border-radius:6px;overflow:hidden;"><div style="width:{{ $s['pct'] }};height:100%;background:{{ $s['color'] }};border-radius:6px;"></div></div>
                        <div style="width:40px;text-align:end;font-size:12.5px;font-weight:700;color:#2A2230;">{{ $s['val'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="{{ $card }}padding:22px 24px;">
                <h3 style="margin:0 0 18px;font-size:15px;font-weight:700;">{{ __('pc.booking_by_emirate') }}</h3>
                @forelse ($stats['byEmirate'] as $e)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:11px;">
                        <div style="width:120px;font-size:12.5px;color:#6B4257;font-weight:500;">{{ $e['name'] }}</div>
                        <div style="flex:1;height:12px;background:#F3E7EE;border-radius:6px;overflow:hidden;"><div style="width:{{ $e['pct'] }};height:100%;background:{{ $e['color'] }};border-radius:6px;"></div></div>
                        <div style="width:40px;text-align:end;font-size:12.5px;font-weight:700;color:#2A2230;">{{ $e['val'] }}</div>
                    </div>
                @empty
                    <div style="font-size:13px;color:#9A8F97;">—</div>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</x-staff-shell>
@endsection
