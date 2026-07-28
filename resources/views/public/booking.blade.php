@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.book_title'))

@php
    $services = [
        'A' => ['title' => __('pc.svc_a_title'), 'tag' => __('pc.svc_a_tag'), 'pkg' => __('pc.svc_a_pkg'), 'elig' => __('pc.svc_a_elig')],
        'B' => ['title' => __('pc.svc_b_title'), 'tag' => __('pc.svc_b_tag'), 'pkg' => __('pc.svc_b_pkg'), 'elig' => __('pc.svc_b_elig')],
        'C' => ['title' => __('pc.svc_c_title'), 'tag' => __('pc.svc_c_tag'), 'pkg' => __('pc.svc_c_pkg'), 'elig' => __('pc.svc_c_elig')],
        'D' => ['title' => __('pc.svc_d_title'), 'tag' => __('pc.svc_d_tag'), 'pkg' => __('pc.svc_d_pkg'), 'elig' => __('pc.svc_d_elig')],
    ];
    $inp = 'display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:14px;';
    $lbl = 'font-size:13px;font-weight:600;color:#6B4257;';
@endphp

@section('content')
<x-public-shell>
    @if (session('booking_ref'))
        <div class="pc-anim" style="max-width:560px;margin:0 auto;padding:54px 24px 70px;">
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:18px;padding:40px;text-align:center;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <div style="width:72px;height:72px;border-radius:50%;background:#E4F4EF;display:flex;align-items:center;justify-content:center;font-size:34px;margin:0 auto 18px;">✓</div>
                <h2 style="font-size:24px;font-weight:700;margin:0 0 8px;">{{ __('pc.book_confirmed') }}</h2>
                <p style="color:#6B6472;margin:0 auto 22px;max-width:460px;line-height:1.55;font-size:14.5px;">{{ __('pc.book_confirm_msg') }}</p>
                <div style="display:inline-flex;align-items:center;gap:10px;background:#FCEFF5;border-radius:12px;padding:12px 22px;margin-bottom:24px;">
                    <span style="font-size:13px;color:#9A8F97;">{{ __('pc.ref_no') }}</span>
                    <span style="font-size:18px;font-weight:700;color:#E6017E;letter-spacing:.04em;">{{ session('booking_ref') }}</span>
                </div>
                <div><a href="{{ route('booking') }}" role="button" style="cursor:pointer;display:inline-block;color:#E6017E;font-weight:700;padding:12px 26px;border:1px solid #F6BFD9;border-radius:12px;text-decoration:none;">{{ __('pc.new_booking') }}</a></div>
            </div>
        </div>
    @else
    <div x-data="{
            step: '{{ $errors->any() ? 'details' : 'services' }}', authMode: 'login', authed: {{ $errors->any() ? 'true' : 'false' }}, svc: '{{ old('service_type', '') }}',
            titles: { A: '{{ __('pc.svc_a_title') }}', B: '{{ __('pc.svc_b_title') }}', C: '{{ __('pc.svc_c_title') }}', D: '{{ __('pc.svc_d_title') }}' },
            request(k) { this.svc = k; this.step = this.authed ? 'details' : 'auth'; window.scrollTo(0,0); },
            authSubmit() { this.authed = true; this.step = 'details'; window.scrollTo(0,0); },
            get num() { return (this.step==='services'||this.step==='auth') ? 1 : (this.step==='details' ? 2 : 3); }
         }"
         style="max-width:880px;margin:0 auto;padding:34px 24px 70px;">

        <div style="text-align:center;margin-bottom:8px;"><img src="{{ asset('assets/ribbon-ring.png') }}" alt="" style="width:56px;height:56px;" /></div>
        <h1 style="text-align:center;font-size:30px;font-weight:700;margin:6px 0 6px;letter-spacing:-.02em;" x-text="step==='services' ? '{{ __('pc.catalog_title') }}' : '{{ __('pc.book_title') }}'"></h1>
        <p style="text-align:center;color:#6B6472;margin:0 0 30px;font-size:15.5px;" x-text="step==='services' ? '{{ __('pc.catalog_sub') }}' : '{{ __('pc.book_sub') }}'"></p>

        {{-- Step indicator --}}
        <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:30px;">
            @foreach ([1 => __('pc.step_services'), 2 => __('pc.step_details'), 3 => __('pc.step_confirm')] as $n => $label)
                <div style="display:flex;align-items:center;gap:10px;">
                    <div :style="num>={{ $n }} ? { background:'#E6017E', color:'#fff' } : { background:'#EAD9E2', color:'#B79EAC' }" style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">{{ $n }}</div>
                    <span :style="num==={{ $n }} ? { color:'#2A2230', fontWeight:'700' } : { color:'#9A8F97', fontWeight:'500' }" style="font-size:14px;">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        {{-- STEP 1: services --}}
        <div x-show="step==='services'" class="pc-anim" style="display:grid;grid-template-columns:repeat(2, 1fr);gap:16px;">
            @foreach ($services as $key => $sv)
                <div style="display:flex;flex-direction:column;background:#fff;border:1px solid #EFE2EA;border-radius:18px;padding:22px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                    <div style="margin-bottom:14px;">
                        <div style="font-size:17px;font-weight:700;line-height:1.25;">{{ $sv['title'] }}</div>
                        <div style="font-size:13px;color:#9A8F97;margin-top:3px;">{{ $sv['tag'] }}</div>
                    </div>
                    <div style="font-size:11.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#E6017E;margin-bottom:8px;">{{ __('pc.package_incl') }}</div>
                    @foreach ($sv['pkg'] as $p)
                        <div style="display:flex;gap:8px;font-size:13.5px;color:#453A44;margin-bottom:6px;line-height:1.4;"><span style="color:#43A047;">✓</span><span>{{ $p }}</span></div>
                    @endforeach
                    <div style="font-size:11.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9A8F97;margin:14px 0 6px;">{{ __('pc.eligibility') }}</div>
                    <div style="font-size:12.5px;color:#6B6472;line-height:1.5;margin-bottom:18px;">{{ $sv['elig'] }}</div>
                    <div @click="request('{{ $key }}')" role="button" style="cursor:pointer;margin-top:auto;text-align:center;font-size:14px;font-weight:700;padding:12px;border-radius:11px;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;box-shadow:0 6px 18px rgba(230,1,126,.2);">{{ __('pc.request_service') }} →</div>
                </div>
            @endforeach
        </div>

        {{-- STEP 1.5: auth gate --}}
        <div x-show="step==='auth'" x-cloak class="pc-anim" style="max-width:460px;margin:0 auto;background:#fff;border:1px solid #EFE2EA;border-radius:20px;padding:32px;box-shadow:0 8px 28px rgba(120,60,90,.08);">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="width:54px;height:54px;border-radius:15px;background:#FCEFF5;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 12px;">🏢</div>
                <h2 style="font-size:21px;font-weight:700;margin:0 0 6px;">{{ __('pc.auth_title') }}</h2>
                <p style="font-size:13.5px;color:#6B6472;margin:0;line-height:1.5;">{{ __('pc.auth_sub') }}</p>
            </div>
            <div style="display:flex;gap:4px;background:#F7EEF3;border-radius:12px;padding:4px;margin-bottom:22px;">
                <div @click="authMode='login'" role="button" :style="authMode==='login' ? { background:'#fff', color:'#E6017E', boxShadow:'0 2px 8px rgba(120,60,90,.08)' } : { background:'transparent', color:'#9A8F97', boxShadow:'none' }" style="cursor:pointer;flex:1;text-align:center;font-size:14px;font-weight:700;padding:10px;border-radius:9px;">{{ __('pc.tab_login') }}</div>
                <div @click="authMode='signup'" role="button" :style="authMode==='signup' ? { background:'#fff', color:'#E6017E', boxShadow:'0 2px 8px rgba(120,60,90,.08)' } : { background:'transparent', color:'#9A8F97', boxShadow:'none' }" style="cursor:pointer;flex:1;text-align:center;font-size:14px;font-weight:700;padding:10px;border-radius:9px;">{{ __('pc.tab_signup') }}</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <template x-if="authMode==='signup'">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <label style="{{ $lbl }}">{{ __('pc.org_name_auth') }} *<input type="text" style="{{ $inp }}" /></label>
                        <label style="{{ $lbl }}">{{ __('pc.contact_auth') }} *<input type="text" style="{{ $inp }}" /></label>
                    </div>
                </template>
                <label style="{{ $lbl }}">{{ __('pc.org_email_field') }} *<input type="email" style="{{ $inp }}" /></label>
                <template x-if="authMode==='signup'">
                    <label style="{{ $lbl }}">{{ __('pc.phone_auth') }} *<input type="tel" style="{{ $inp }}" /></label>
                </template>
                <label style="{{ $lbl }}">{{ __('pc.password_field') }} *<input type="password" style="{{ $inp }}" /></label>
                <template x-if="authMode==='signup'">
                    <label style="{{ $lbl }}">{{ __('pc.confirm_pw_field') }} *<input type="password" style="{{ $inp }}" /></label>
                </template>
            </div>
            <div @click="authSubmit()" role="button" style="cursor:pointer;margin-top:22px;text-align:center;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;font-size:15px;padding:13px;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);"><span x-text="authMode==='login' ? '{{ __('pc.login_btn') }}' : '{{ __('pc.signup_btn') }}'"></span></div>
            <div style="margin-top:16px;font-size:12px;color:#9A8F97;text-align:center;line-height:1.5;">{{ __('pc.auth_note') }}</div>
            <div @click="step='services'" role="button" style="cursor:pointer;margin-top:14px;text-align:center;font-size:13.5px;font-weight:600;color:#6B6472;">{{ __('pc.back_to_services') }}</div>
        </div>

        {{-- STEP 2: org details (real submission) --}}
        <form x-show="step==='details'" x-cloak method="POST" action="{{ route('booking.store') }}" class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:18px;padding:28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            @csrf
            <input type="hidden" name="service_type" :value="svc" />
            @if ($errors->any())
                <div style="margin-bottom:16px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:12px 16px;color:#9A2E2E;font-size:13px;">{{ $errors->first() }}</div>
            @endif
            <div style="display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:12.5px;font-weight:700;padding:7px 13px;border-radius:999px;margin-bottom:20px;"><span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span><span x-text="titles[svc]"></span></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                <label style="{{ $lbl }}">{{ __('pc.org_name') }} *<input type="text" name="organisation" value="{{ old('organisation') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.contact_person') }} *<input type="text" name="contact_person" value="{{ old('contact_person') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.email') }} *<input type="email" name="email" value="{{ old('email') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.mobile') }} *<input type="tel" name="mobile" value="{{ old('mobile') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.emirate') }}<select name="emirate" style="{{ $inp }}background:#fff;"><option value="">—</option>@foreach (['abu_dhabi','dubai','sharjah','ajman','umm_al_quwain','ras_al_khaimah','fujairah'] as $em)<option value="{{ $em }}">{{ __('pc.em_'.$em) }}</option>@endforeach</select></label>
                <label style="{{ $lbl }}">{{ __('pc.est_count') }}<input type="number" name="estimated_participants" value="{{ old('estimated_participants') }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.pref_date') }}<input type="date" name="event_date" value="{{ old('event_date') }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.venue') }}<input type="text" name="venue" value="{{ old('venue') }}" style="{{ $inp }}" /></label>
                <label style="grid-column:1 / -1;{{ $lbl }}">{{ __('pc.notes') }}<textarea name="notes" rows="3" style="{{ $inp }}resize:vertical;">{{ old('notes') }}</textarea></label>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:24px;">
                <div @click="step='services'" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;padding:13px 24px;border-radius:12px;border:1px solid #E3D2DC;">← {{ __('pc.back') }}</div>
                <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:13px 30px;border:none;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);">{{ __('pc.submit_booking') }}</button>
            </div>
        </form>

        {{-- STEP 3: confirmation --}}
        <div x-show="step==='confirm'" x-cloak class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:18px;padding:40px;text-align:center;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div style="width:72px;height:72px;border-radius:50%;background:#E4F4EF;display:flex;align-items:center;justify-content:center;font-size:34px;margin:0 auto 18px;">✓</div>
            <h2 style="font-size:24px;font-weight:700;margin:0 0 8px;">{{ __('pc.book_confirmed') }}</h2>
            <p style="color:#6B6472;margin:0 auto 22px;max-width:460px;line-height:1.55;font-size:14.5px;">{{ __('pc.book_confirm_msg') }}</p>
            <div style="display:inline-flex;align-items:center;gap:10px;background:#FCEFF5;border-radius:12px;padding:12px 22px;margin-bottom:24px;">
                <span style="font-size:13px;color:#9A8F97;">{{ __('pc.ref_no') }}</span>
                <span style="font-size:18px;font-weight:700;color:#E6017E;letter-spacing:.04em;">PCB-2026-4821</span>
            </div>
            <div style="max-width:420px;margin:0 auto;text-align:start;">
                <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:#FAF4F7;border-radius:10px;margin-bottom:8px;font-size:14px;font-weight:600;"><span style="color:#43A047;">✓</span><span x-text="titles[svc]"></span></div>
            </div>
            <a href="{{ url('/') }}" role="button" style="cursor:pointer;display:inline-block;margin-top:24px;color:#E6017E;font-weight:700;padding:12px 26px;border:1px solid #F6BFD9;border-radius:12px;text-decoration:none;">{{ __('pc.new_booking') }}</a>
        </div>
    </div>
    @endif
</x-public-shell>
@endsection
