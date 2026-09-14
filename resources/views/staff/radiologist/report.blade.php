@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_patient_report'))

@php
    $inp  = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $lbl  = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 26px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $sectionHead = fn($n, $title) => '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;"><div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">'.$n.'</div><h3 style="margin:0;font-size:16px;font-weight:700;">'.$title.'</h3></div>';
    $sent = $record->radiology_report_sent_at !== null;
    $delivery = $record->report?->delivery ?? [];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:860px;margin:0 auto;padding-bottom:30px;">
        <a href="{{ route('radiologist.reports') }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.nav_patient_report') }}</a>

        @if (session('status'))
            <div style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="margin-bottom:14px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
                <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- 1. Everything the mammographer filed, read-only. --}}
        <div style="{{ $card }}">
            {!! $sectionHead(1, __('pc.rad_patient_details')) !!}
            <div class="pc-cols-2" style="gap:16px;">
                <div>
                    <div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.patient_number') }}</div>
                    <div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->pc_number ?? $record->ref_no }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.full_name') }}</div>
                    <div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->full_name }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.email') }}</div>
                    <div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->email ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.pc_number') }}</div>
                    <div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->manual_pc_number ?: '—' }}</div>
                </div>
            </div>
        </div>

        {{-- 2. The mammography findings as recorded at the screening. --}}
        <div style="{{ $card }}">
            {!! $sectionHead(2, __('pc.rad_mammo_findings')) !!}
            <div style="font-size:11px;color:#9A8F97;font-weight:600;margin-bottom:8px;">
                {{ __('pc.mammographer_col') }}: {{ $record->mammographer?->name ?? '—' }}
                @if ($finding?->submitted_at)
                    · {{ $finding->submitted_at->format('d M Y, H:i') }}
                @endif
            </div>
            <div style="background:#FAF4F7;border:1px solid #F3E7EE;border-radius:12px;padding:16px 18px;font-size:13.5px;line-height:1.6;color:#453A44;white-space:pre-wrap;">{{ $finding?->findings ?: __('pc.findings_none_yet') }}</div>
        </div>

        {{-- 3. The final PDF report from the radiology medical system. --}}
        <form method="POST" action="{{ route('radiologist.report.upload', $record) }}" enctype="multipart/form-data">
            @csrf
            <div style="{{ $card }}">
                {!! $sectionHead(3, __('pc.rad_final_report')) !!}
                <p style="margin:-8px 0 16px;font-size:13px;color:#6B6472;line-height:1.5;">{{ __('pc.rad_final_report_hint') }}</p>

                @if ($record->radiology_report_path)
                    <div style="display:flex;align-items:center;justify-content:space-between;background:#F7EEF3;border-radius:10px;padding:12px 16px;margin-bottom:14px;">
                        <div style="font-size:13px;color:#453A44;">📄 {{ __('pc.report_uploaded_lbl') }} · {{ optional($record->radiology_report_uploaded_at)->format('d M Y, H:i') }}</div>
                        <a href="{{ route('radiologist.report.file', $record) }}" target="_blank" style="text-decoration:none;font-size:12.5px;font-weight:700;color:#E6017E;">{{ __('pc.view_report') }}</a>
                    </div>
                @endif

                <label style="{{ $lbl }}">{{ __('pc.report_file') }}<input type="file" name="report" accept="application/pdf" required style="{{ $inp }}padding:8px 10px;" /></label>

                <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                    <button type="submit" style="cursor:pointer;background:#fff;color:#6B4257;font-weight:700;font-size:13.5px;padding:11px 22px;border:1px solid #E3D2DC;border-radius:11px;">{{ $record->radiology_report_path ? __('pc.rad_replace_report') : __('pc.save_report') }}</button>
                </div>
            </div>
        </form>

        {{-- 4. Send the PDF to the patient. --}}
        <div style="{{ $card }}display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
            <div>
                <div style="font-size:14px;font-weight:700;">{{ __('pc.rad_send_report') }}</div>
                <div style="font-size:12px;color:#B7A9B2;margin-top:2px;">📧 {{ __('pc.rad_send_channels') }}</div>
                <div style="font-size:12.5px;color:#9A8F97;margin-top:5px;">
                    @if ($sent)
                        {{ __('pc.report_sent_lbl') }} · {{ $record->radiology_report_sent_at->format('d M Y, H:i') }}
                        @if (! empty($delivery))
                            <div style="margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;">
                                @foreach (['email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $ch=>$chLabel)
                                    @isset($delivery[$ch])
                                        @php($ok = in_array($delivery[$ch], ['sent','logged'], true))
                                        <span style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;color:{{ $ok ? '#2E7D32' : '#9A2E2E' }};background:{{ $ok ? '#E4F4EF' : '#FBE4E4' }};">{{ $chLabel }}: {{ $delivery[$ch] }}</span>
                                    @endisset
                                @endforeach
                            </div>
                        @endif
                    @elseif (! $record->radiology_report_path)
                        {{ __('pc.rad_upload_first') }}
                    @else
                        {{ __('pc.not_sent') }}
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('radiologist.report.send', $record) }}">
                @csrf
                @php($canSend = (bool) $record->radiology_report_path)
                <button type="submit" @disabled(! $canSend) style="cursor:pointer;background:{{ $canSend ? 'linear-gradient(90deg,#E6017E,#C0116E)' : '#E3D2DC' }};color:#fff;font-weight:700;font-size:14px;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">✉ {{ $sent ? __('pc.rad_resend_report') : __('pc.rad_send_report') }}</button>
            </form>
        </div>
    </div>
</x-staff-shell>
@endsection
