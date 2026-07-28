@extends('layouts.app')

@section('title', 'Pink Caravan — '.__('pc.login_title'))

@php
    $inp = 'display:block;width:100%;margin-top:6px;padding:12px 14px;border:1px solid #E3D2DC;border-radius:11px;font-size:15px;';
    $lbl = 'display:block;text-align:start;font-size:13px;font-weight:600;color:#6B4257;';
@endphp

@section('content')
<style>
    .pc-login { display:grid; grid-template-columns:1.05fr 1fr; min-height:100vh; }
    .pc-login__hero { position:relative; overflow:hidden; }
    .pc-login__hero img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:60% center; }
    .pc-login__scrim { position:absolute; inset:0; background:linear-gradient(180deg, rgba(230,1,126,.18) 0%, rgba(42,22,34,0) 32%, rgba(42,22,34,.55) 60%, rgba(42,22,34,.92) 100%); }
    .pc-login__form { display:flex; align-items:center; justify-content:center; padding:28px; background:radial-gradient(120% 90% at 85% 0%, #FCE7F0 0%, #F4EEF1 46%, #F4EEF1 100%); }
    @media (max-width: 920px) {
        .pc-login { grid-template-columns:1fr; }
        .pc-login__hero { display:none; }
    }
</style>

<div class="pc-login">
    {{-- Left: brand hero photo --}}
    <div class="pc-login__hero">
        <img src="{{ asset('assets/login-hero.jpg') }}" alt="Pink Caravan mobile screening clinic" />
        <div class="pc-login__scrim"></div>
        <div style="position:absolute;inset-inline:40px;bottom:44px;color:#fff;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
                <img src="{{ asset('assets/ribbon-ring.png') }}" alt="" style="width:40px;height:40px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.3));" />
                <div style="line-height:1.2;">
                    <div style="font-size:18px;font-weight:700;letter-spacing:-.01em;">Pink Caravan · القافلة الوردية</div>
                    <div style="font-size:12px;color:rgba(255,255,255,.85);">Friends of Cancer Patients (FOCP)</div>
                </div>
            </div>
            <h2 style="font-size:30px;font-weight:700;line-height:1.15;margin:0 0 10px;max-width:460px;text-shadow:0 2px 16px rgba(0,0,0,.35);">{{ __('pc.login_hero_title') }}</h2>
            <p style="font-size:15px;color:rgba(255,255,255,.9);margin:0;max-width:420px;text-shadow:0 1px 10px rgba(0,0,0,.35);">{{ __('pc.login_hero_sub') }}</p>
        </div>
    </div>

    {{-- Right: sign-in --}}
    <div class="pc-login__form">
        <div style="width:100%;max-width:420px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                <a href="{{ url('/') }}"><img src="{{ asset('assets/pink-caravan-wordmark.png') }}" alt="Pink Caravan" style="height:46px;width:auto;" /></a>
                <div style="display:flex;align-items:center;gap:8px;">
                    <x-lang-toggle variant="hub" />
                    <a href="{{ url('/') }}" role="button" style="cursor:pointer;background:#fff;border:1px solid #EEDCE6;border-radius:999px;padding:8px 14px;font-weight:600;color:#6B6472;font-size:13px;text-decoration:none;">✕</a>
                </div>
            </div>

            <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:20px;padding:34px 32px;box-shadow:0 10px 34px rgba(120,60,90,.10);">
                <div style="margin-bottom:22px;">
                    <div style="font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#E6017E;margin-bottom:8px;">{{ __('pc.staff_portal') }}</div>
                    <h1 style="font-size:25px;font-weight:700;margin:0 0 6px;">{{ __('pc.login_title') }}</h1>
                    <p style="font-size:13.5px;color:#6B6472;margin:0;line-height:1.5;">{{ __('pc.login_sub') }}</p>
                </div>

                @if ($errors->any())
                    <div style="margin-bottom:18px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:12px 16px;color:#9A2E2E;font-size:13.5px;text-align:center;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <label style="{{ $lbl }}margin-bottom:16px;">{{ __('pc.email') }}
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" style="{{ $inp }}" />
                    </label>
                    <label style="{{ $lbl }}margin-bottom:16px;">{{ __('pc.password') }}
                        <input type="password" name="password" required autocomplete="current-password" style="{{ $inp }}" />
                    </label>
                    <label style="display:flex;align-items:center;gap:9px;font-size:13px;color:#6B6472;margin-bottom:22px;cursor:pointer;">
                        <input type="checkbox" name="remember" value="1" style="width:16px;height:16px;accent-color:#E6017E;" />
                        {{ __('pc.remember_me') }}
                    </label>
                    <button type="submit" style="cursor:pointer;width:100%;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;font-size:15px;padding:14px;border:none;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);">{{ __('pc.sign_in') }}</button>
                </form>

                @if (config('app.debug'))
                    <div style="margin-top:20px;padding:12px 14px;background:#FAF4F7;border-radius:12px;font-size:11.5px;color:#9A8F97;line-height:1.6;">
                        <div style="font-weight:700;color:#6B4257;margin-bottom:4px;">{{ __('pc.demo_accounts') }}</div>
                        anish@focp.ae · l.hassan@focp.ae · s.nuaimi@focp.ae · mariam.s@focp.ae
                    </div>
                @endif
            </div>

            <p style="text-align:center;font-size:12px;color:#9A8F97;margin-top:18px;">{{ __('pc.hub_foot') }}</p>
        </div>
    </div>
</div>
@endsection
