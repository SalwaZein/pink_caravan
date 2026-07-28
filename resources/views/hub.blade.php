@extends('layouts.app')

@section('title', 'Pink Caravan — '.__('pc.platform'))

@php
    // Public flows are open; staff roles go through the login page (routed by role after sign-in).
    $loginUrl = route('login');
    $roleCards = [
        ['icon' => '🏢', 'tint' => '#FCE7F0', 'title' => __('pc.role_partner_title'), 'desc' => __('pc.role_partner_desc'), 'url' => url('/booking'), 'cta' => __('pc.enter')],
        ['icon' => '💗', 'tint' => '#F3E6FA', 'title' => __('pc.role_patient_title'), 'desc' => __('pc.role_patient_desc'), 'url' => url('/patient'), 'cta' => __('pc.enter')],
        ['icon' => '🩺', 'tint' => '#E4F4EF', 'title' => __('pc.role_nurse_title'),   'desc' => __('pc.role_nurse_desc'),   'url' => $loginUrl, 'cta' => __('pc.staff_login_cta')],
        ['icon' => '👩‍⚕️', 'tint' => '#E6EEFB', 'title' => __('pc.role_doctor_title'),  'desc' => __('pc.role_doctor_desc'),  'url' => $loginUrl, 'cta' => __('pc.staff_login_cta')],
        ['icon' => '🗂️', 'tint' => '#FDEFE0', 'title' => __('pc.role_clinic_title'),  'desc' => __('pc.role_clinic_desc'),  'url' => $loginUrl, 'cta' => __('pc.staff_login_cta')],
        ['icon' => '📊', 'tint' => '#FBE4EC', 'title' => __('pc.role_super_title'),   'desc' => __('pc.role_super_desc'),   'url' => $loginUrl, 'cta' => __('pc.staff_login_cta')],
    ];
@endphp

@section('content')
<div style="min-height:100vh;display:flex;flex-direction:column;background:radial-gradient(120% 90% at 80% 0%, #FCE7F0 0%, #F4EEF1 45%, #F4EEF1 100%);">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:22px 40px;">
        <img src="{{ asset('assets/pink-caravan-wordmark.png') }}" alt="Pink Caravan" style="height:62px;width:auto;" />
        <x-lang-toggle variant="hub" />
    </div>

    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px 24px 60px;text-align:center;">
        <img src="{{ asset('assets/ribbon-ring.png') }}" alt="" style="width:92px;height:92px;margin-bottom:22px;" />
        <div style="font-size:13px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#E6017E;margin-bottom:12px;">{{ __('pc.hub_kicker') }}</div>
        <h1 style="font-size:44px;line-height:1.08;font-weight:700;margin:0 0 14px;max-width:760px;letter-spacing:-.02em;">{{ __('pc.hub_title') }}</h1>
        <p style="font-size:17px;color:#6B6472;margin:0 0 44px;max-width:560px;line-height:1.5;">{{ __('pc.hub_sub') }}</p>

        <div style="display:grid;grid-template-columns:repeat(3, minmax(220px, 260px));gap:20px;width:100%;max-width:860px;">
            @foreach ($roleCards as $r)
                <a href="{{ $r['url'] }}" class="pc-anim pc-card-hover"
                   style="cursor:pointer;background:#fff;border:1px solid #F0E1E9;border-radius:20px;padding:26px 22px;text-align:start;box-shadow:0 6px 22px rgba(120,60,90,.06);text-decoration:none;color:inherit;display:block;">
                    <div style="width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;background:{{ $r['tint'] }};margin-bottom:18px;">{{ $r['icon'] }}</div>
                    <div style="font-size:18px;font-weight:700;margin-bottom:6px;">{{ $r['title'] }}</div>
                    <div style="font-size:13.5px;color:#6B6472;line-height:1.45;">{{ $r['desc'] }}</div>
                    <div style="margin-top:16px;font-size:13px;font-weight:600;color:#E6017E;">{{ $r['cta'] }} →</div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:40px;font-size:12.5px;color:#9A8F97;">{{ __('pc.hub_foot') }}</div>
    </div>
</div>
@endsection
