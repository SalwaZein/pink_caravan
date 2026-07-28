@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.pat_title'))

@section('content')
<x-public-shell>
    <div style="max-width:460px;margin:0 auto;padding:54px 24px 70px;">

        @if ($step === 1)
            <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:20px;padding:36px 32px;box-shadow:0 8px 28px rgba(120,60,90,.08);text-align:center;">
                <div style="font-size:42px;margin-bottom:10px;">💗</div>
                <h1 style="font-size:24px;font-weight:700;margin:0 0 8px;">{{ __('pc.pat_title') }}</h1>
                <p style="color:#6B6472;font-size:14.5px;line-height:1.5;margin:0 0 26px;">{{ __('pc.pat_sub') }}</p>
                <form method="POST" action="{{ route('patient.otp.send') }}">
                    @csrf
                    <label style="display:block;text-align:start;font-size:13px;font-weight:600;color:#6B4257;margin-bottom:18px;">{{ __('pc.mobile_no') }}
                        <input type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="05X XXX XXXX" required style="display:block;width:100%;margin-top:6px;padding:13px 14px;border:1px solid #E3D2DC;border-radius:11px;font-size:15px;" />
                    </label>
                    @error('mobile')<div style="color:#C62828;font-size:12.5px;text-align:start;margin-bottom:12px;">{{ $message }}</div>@enderror
                    <button type="submit" style="cursor:pointer;width:100%;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:14px;border:none;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);">{{ __('pc.send_code') }}</button>
                </form>
            </div>

        @elseif ($step === 2)
            <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:20px;padding:36px 32px;box-shadow:0 8px 28px rgba(120,60,90,.08);text-align:center;">
                <div style="font-size:42px;margin-bottom:10px;">📱</div>
                <h1 style="font-size:22px;font-weight:700;margin:0 0 8px;">{{ __('pc.enter_code') }}</h1>
                <p style="color:#6B6472;font-size:14px;margin:0 0 20px;">{{ __('pc.otp_sent') }} <b style="color:#2A2230;">{{ $mobile }}</b></p>

                @if (!empty($devCode))
                    <div style="background:#FAF4F7;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:12.5px;color:#9A8F97;">{{ __('pc.dev_code_hint') }} <b style="color:#E6017E;letter-spacing:.1em;">{{ $devCode }}</b></div>
                @endif

                <form method="POST" action="{{ route('patient.verify') }}">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required autofocus placeholder="••••" style="width:180px;text-align:center;font-size:28px;font-weight:700;letter-spacing:.5em;padding:12px;border:2px solid #E6017E;border-radius:13px;color:#2A2230;margin-bottom:6px;" />
                    @error('code')<div style="color:#C62828;font-size:12.5px;margin-bottom:8px;">{{ $message }}</div>@enderror
                    <button type="submit" style="cursor:pointer;width:100%;margin-top:16px;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:14px;border:none;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);">{{ __('pc.verify') }}</button>
                </form>
                <div style="display:flex;justify-content:center;gap:18px;margin-top:16px;font-size:13px;font-weight:600;">
                    <a href="{{ route('patient') }}" style="color:#9A8F97;text-decoration:none;">{{ __('pc.change_no') }}</a>
                </div>
            </div>

        @else
            <div class="pc-anim">
                <div style="background:#fff;border:1px solid #EFE2EA;border-radius:20px;padding:30px;box-shadow:0 8px 28px rgba(120,60,90,.08);">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                        <div style="width:52px;height:52px;border-radius:14px;background:#E4F4EF;display:flex;align-items:center;justify-content:center;font-size:26px;">✓</div>
                        <div><div style="font-size:12px;color:#9A8F97;font-weight:600;">{{ __('pc.report_ready') }}</div><div style="font-size:18px;font-weight:700;">{{ __('pc.your_report') }}</div></div>
                    </div>
                    @php($result = $record->finalResult())
                    <div style="background:#FAF4F7;border-radius:12px;padding:16px 18px;margin-bottom:18px;font-size:13.5px;line-height:1.9;">
                        <div style="display:flex;justify-content:space-between;"><span style="color:#9A8F97;">Name</span><b>{{ $record->patient->full_name }}</b></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#9A8F97;">Ref No</span><b>{{ $record->ref_no }}</b></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#9A8F97;">Examination</span><b>{{ optional($record->examination?->exam_date ?? $record->submitted_at)->format('d M Y') }}</b></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;"><span style="color:#9A8F97;">Result</span><b style="color:{{ $result==='abnormal'?'#C62828':'#43A047' }};background:{{ $result==='abnormal'?'#FBE4E4':'#E4F4EF' }};padding:2px 10px;border-radius:999px;font-size:12px;">{{ $result==='abnormal' ? __('pc.abnormal') : __('pc.normal') }}</b></div>
                    </div>
                    <a href="{{ route('patient.report.document') }}" target="_blank" style="display:block;text-align:center;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:14px;border-radius:12px;box-shadow:0 6px 18px rgba(230,1,126,.22);margin-bottom:10px;text-decoration:none;">📄 {{ __('pc.view_report') }}</a>
                    <a href="{{ route('patient.report.download') }}" style="display:block;text-align:center;background:#fff;border:1px solid #E3D2DC;color:#6B4257;font-weight:700;padding:13px;border-radius:12px;margin-bottom:12px;text-decoration:none;">⬇ {{ __('pc.download') }}</a>
                    <div style="display:flex;gap:10px;">
                        <div role="button" style="cursor:pointer;flex:1;text-align:center;border:1px solid #E3D2DC;border-radius:11px;padding:11px;font-size:13.5px;font-weight:600;color:#6B4257;">✉ {{ __('pc.email_report') }}</div>
                        <div role="button" style="cursor:pointer;flex:1;text-align:center;border:1px solid #E3D2DC;border-radius:11px;padding:11px;font-size:13.5px;font-weight:600;color:#6B4257;">💬 {{ __('pc.whatsapp') }}</div>
                    </div>
                </div>
                <p style="text-align:center;font-size:12px;color:#9A8F97;margin-top:16px;">🔒 {{ __('pc.report_privacy') }} · <a href="{{ route('patient.reset') }}" style="color:#9A8F97;">{{ __('pc.exit') }}</a></p>
            </div>
        @endif
    </div>
</x-public-shell>
@endsection
