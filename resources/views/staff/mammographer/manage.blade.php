@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.manage_report'))

@php
    $inp = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $lbl = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 26px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $sent = $record->report_sent_at !== null;
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:860px;margin:0 auto;padding-bottom:30px;">
        <a href="{{ route('mammographer.queue') }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.nav_mammo_reports') }}</a>

        @if (session('status'))
            <div style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="margin-bottom:14px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
                <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Header + team (who managed / examined / reviewed) --}}
        <div style="{{ $card }}display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;">
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.reg_number') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->ref_no }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.patient_col') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->full_name }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.result_col') }}</div>
                @php($res = \App\Support\RecordPresenter::resultMeta()[$record->finalResult()] ?? \App\Support\RecordPresenter::resultMeta()[null])
                <div style="margin-top:3px;"><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $res['c'] }};background:{{ $res['bg'] }};">{{ $res['label'] }}</span></div>
            </div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.status') }}</div>
                @php($stMeta = \App\Support\RecordPresenter::statusMeta()[$record->status] ?? \App\Support\RecordPresenter::statusMeta()['in_review'])
                <div style="margin-top:3px;"><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $sent ? '#0E7C6B' : $stMeta['c'] }};background:{{ $sent ? '#DFF3EF' : $stMeta['bg'] }};">{{ $sent ? __('pc.status_report_sent') : $stMeta['label'] }}</span></div>
            </div>
            <div style="grid-column:1 / -1;display:flex;gap:26px;border-top:1px solid #F3E7EE;padding-top:14px;">
                <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.managed_by') }}</div><div style="font-size:13px;font-weight:600;margin-top:2px;">{{ $record->mammographer?->name ?? '—' }}</div></div>
                <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.examined_by') }}</div><div style="font-size:13px;font-weight:600;margin-top:2px;">{{ $record->doctor?->name ?? '—' }}</div></div>
                <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.reviewed_by') }}</div><div style="font-size:13px;font-weight:600;margin-top:2px;">{{ $record->nurse?->name ?? '—' }}</div></div>
            </div>
        </div>

        {{-- Editable patient details + mammogram report upload --}}
        <form method="POST" action="{{ route('mammographer.record.update', $record) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div style="{{ $card }}">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">✎</div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.reopen_edit') }}</h3>
                </div>
                <p style="font-size:12px;color:#9A8F97;margin:0 0 16px;">{{ __('pc.reopen_hint') }}</p>
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:14px;">
                    <label style="{{ $lbl }}">{{ __('pc.pc_number') }}<input name="manual_pc_number" value="{{ old('manual_pc_number', $patient->manual_pc_number) }}" style="{{ $inp }}" /></label>
                    <label style="{{ $lbl }}">{{ __('pc.full_name') }} *<input name="full_name" value="{{ old('full_name', $patient->full_name) }}" required style="{{ $inp }}" /></label>
                    <label style="{{ $lbl }}">{{ __('pc.email') }}<input type="email" name="email" value="{{ old('email', $patient->email) }}" style="{{ $inp }}" /></label>
                    <label style="{{ $lbl }}">{{ __('pc.mobile1') }} *<input type="tel" name="mobile1" value="{{ old('mobile1', $patient->mobile1) }}" required style="{{ $inp }}" /></label>
                    <label style="{{ $lbl }}">{{ __('pc.mobile2') }}<input type="tel" name="mobile2" value="{{ old('mobile2', $patient->mobile2) }}" style="{{ $inp }}" /></label>
                </div>

                <div style="margin-top:20px;border-top:1px solid #F3E7EE;padding-top:18px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">🩻</div>
                        <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.upload_mammo_report') }}</h3>
                    </div>
                    @if ($record->mammogram_report_path)
                        <div style="display:flex;align-items:center;justify-content:space-between;background:#F7EEF3;border-radius:10px;padding:12px 16px;margin-bottom:12px;">
                            <div style="font-size:13px;color:#453A44;">📄 {{ __('pc.report_uploaded_lbl') }} · {{ optional($record->report_uploaded_at)->format('d M Y, H:i') }}</div>
                            <a href="{{ route('mammographer.record.report', $record) }}" target="_blank" style="text-decoration:none;font-size:12.5px;font-weight:700;color:#E6017E;">{{ __('pc.view_report') }}</a>
                        </div>
                    @endif
                    <label style="{{ $lbl }}">{{ __('pc.report_file') }}<input type="file" name="report" accept="application/pdf" style="{{ $inp }}padding:8px 10px;" /></label>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                    <button type="submit" style="cursor:pointer;background:#fff;color:#6B4257;font-weight:700;font-size:13.5px;padding:11px 22px;border:1px solid #E3D2DC;border-radius:11px;">{{ __('pc.save_report') }}</button>
                </div>
            </div>
        </form>

        {{-- Send to patient --}}
        <div style="{{ $card }}display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:14px;font-weight:700;">{{ __('pc.send_report') }}</div>
                <div style="font-size:12px;color:#B7A9B2;margin-top:2px;">📧 {{ __('pc.notify_channels') }}</div>
                <div style="font-size:12.5px;color:#9A8F97;margin-top:5px;">
                    @if ($record->report_sent_at)
                        {{ __('pc.report_sent_lbl') }} · {{ $record->report_sent_at->format('d M Y, H:i') }}
                        @php($del = $record->report?->delivery ?? [])
                        @if (!empty($del))
                            <div style="margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;">
                                @foreach (['email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $ch=>$lbl)
                                    @isset($del[$ch])
                                        @php($ok = in_array($del[$ch], ['sent','logged'], true))
                                        <span style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;color:{{ $ok ? '#2E7D32' : '#9A2E2E' }};background:{{ $ok ? '#E4F4EF' : '#FBE4E4' }};">{{ $lbl }}: {{ $del[$ch] }}</span>
                                    @endisset
                                @endforeach
                            </div>
                        @endif
                    @elseif (! $record->mammogram_report_path)
                        {{ __('pc.report_needed_first') }}
                    @else
                        {{ __('pc.not_sent') }}
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('mammographer.record.send', $record) }}">
                @csrf
                <button type="submit" @disabled(! $record->mammogram_report_path) style="cursor:pointer;background:{{ $record->mammogram_report_path ? 'linear-gradient(90deg,#E6017E,#C0116E)' : '#E3D2DC' }};color:#fff;font-weight:700;font-size:14px;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">✉ {{ $record->report_sent_at ? __('pc.send_report') : __('pc.send_report') }}</button>
            </form>
        </div>
    </div>
</x-staff-shell>
@endsection
