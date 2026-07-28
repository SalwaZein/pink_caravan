@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_audit_log'))

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        @foreach ($audit as $a)
            <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid #F3E7EE;">
                <div style="width:38px;height:38px;border-radius:10px;background:{{ $a['tint'] }};display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;">{{ $a['ic'] }}</div>
                <div style="flex:1;"><div style="font-size:14px;"><b>{{ $a['who'] }}</b> <span style="color:#6B6472;">{{ $a['act'] }}</span></div><div style="font-size:12px;color:#9A8F97;margin-top:2px;">{{ $a['ent'] }}</div></div>
                <div style="font-size:12px;color:#B7A9B2;white-space:nowrap;">{{ $a['t'] }}</div>
            </div>
        @endforeach
    </div>
</x-staff-shell>
@endsection
