@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_bookings'))

@php
    $svcTitles = ['A'=>__('pc.svc_a_title'),'B'=>__('pc.svc_b_title'),'C'=>__('pc.svc_c_title'),'D'=>__('pc.svc_d_title')];
    $tints = ['#16A6A6','#2A6FDB','#7E4CC4','#F7941E','#E6017E','#43A047'];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        <div style="display:grid;grid-template-columns:130px 1.4fr 1.4fr 130px 90px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;">
            <div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.org_name') }}</div><div>{{ __('pc.services') }}</div><div>{{ __('pc.emirate') }}</div><div style="text-align:end;">{{ __('pc.today_col') }}</div>
        </div>
        @forelse ($bookings as $i => $b)
            <div style="display:grid;grid-template-columns:130px 1.4fr 1.4fr 130px 90px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $b->ref_no }}</div>
                <div><div style="font-weight:600;">{{ $b->organisation }}</div><div style="font-size:11.5px;color:#9A8F97;">{{ $b->contact_person }} · {{ $b->email }}</div></div>
                <div style="font-size:12.5px;color:#6B6472;">{{ $b->services->map(fn($s) => $svcTitles[$s->service_type] ?? $s->service_type)->join(', ') }}</div>
                <div style="color:#6B6472;font-size:12.5px;">{{ $b->emirate ? __('pc.em_'.$b->emirate) : '—' }}</div>
                <div style="text-align:end;font-size:12px;color:#9A8F97;">{{ $b->created_at->diffForHumans(null, true) }}</div>
            </div>
        @empty
            <div style="padding:34px 20px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.no_bookings') }}</div>
        @endforelse
    </div>
</x-staff-shell>
@endsection
