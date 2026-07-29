@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_bookings'))

@php
    $svcTitles = ['A'=>__('pc.svc_a_title'),'B'=>__('pc.svc_b_title'),'C'=>__('pc.svc_c_title'),'D'=>__('pc.svc_d_title')];
    $cols = '128px 1.5fr 1.4fr 128px 92px 96px';
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    @if (session('status'))
        <div class="pc-anim" style="margin-bottom:16px;display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:13px;font-weight:700;padding:10px 16px;border-radius:12px;">
            <span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span>{{ session('status') }}
        </div>
    @endif

    <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;">
            <div>{{ __('pc.ref_no_col') }}</div><div>{{ __('pc.org_name') }}</div><div>{{ __('pc.services') }}</div><div>{{ __('pc.booking_status_col') }}</div><div style="text-align:end;">{{ __('pc.today_col') }}</div><div></div>
        </div>
        @forelse ($bookings as $b)
            @php([$sc, $sbg] = $b->statusColors())
            <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                <div style="font-weight:600;color:#6B4257;font-size:12.5px;">{{ $b->ref_no }}</div>
                <div><div style="font-weight:600;">{{ $b->organisation }}</div><div style="font-size:11.5px;color:#9A8F97;">{{ $b->contact_person }} · {{ $b->email }}</div></div>
                <div style="font-size:12.5px;color:#6B6472;">{{ $b->services->map(fn($s) => $svcTitles[$s->service_type] ?? $s->service_type)->join(', ') }}</div>
                <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $sc }};background:{{ $sbg }};">{{ $b->statusLabel() }}</span></div>
                <div style="text-align:end;font-size:12px;color:#9A8F97;">{{ $b->created_at->diffForHumans(null, true) }}</div>
                <div style="text-align:end;">
                    <a href="{{ route('super.bookings.show', $b) }}" style="font-size:12.5px;font-weight:700;color:#E6017E;text-decoration:none;">{{ __('pc.booking_review') }} →</a>
                </div>
            </div>
        @empty
            <div style="padding:34px 20px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.no_bookings') }}</div>
        @endforelse
    </div>
</x-staff-shell>
@endsection
