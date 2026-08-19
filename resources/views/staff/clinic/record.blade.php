@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.record_details'))

@php
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $lbl  = 'font-size:11px;color:#9A8F97;font-weight:600;';
    $val  = 'font-size:13.5px;font-weight:600;color:#2A2230;margin-top:2px;';
    $secHead = fn ($t) => '<h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#2A2230;">'.$t.'</h3>';

    $p   = $record->patient;
    $ex  = $record->examination;
    $ph  = $record->personal_history ?? [];
    $phn = $record->personal_history_notes ?? [];
    $fh  = $record->family_history ?? [];
    $personalItems = ['lumpectomy','biopsy','hyperplasia','hrt','personal_bc','ovarian','fam_ovarian','fam_male_bc','implant'];
    $positiveHistory = array_values(array_filter($personalItems, fn ($k) => ($ph[$k] ?? 'no') === 'yes'));
    $degLabels = ['deg1' => __('pc.deg1'), 'deg2' => __('pc.deg2'), 'deg3' => __('pc.deg3')];
    $st = \App\Support\RecordPresenter::statusMeta()[$record->status] ?? \App\Support\RecordPresenter::statusMeta()['draft'];
    $res = \App\Support\RecordPresenter::resultMeta()[$record->finalResult()] ?? \App\Support\RecordPresenter::resultMeta()[null];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:940px;margin:0 auto;padding-bottom:30px;display:flex;flex-direction:column;gap:16px;">
        <a href="{{ url('/clinic/queue') }}" style="display:inline-block;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.nav_clinic_queue') }}</a>

        {{-- Header --}}
        <div style="{{ $card }}padding:20px 24px;display:grid;grid-template-columns:repeat(5, 1fr);gap:16px;">
            <div><div style="{{ $lbl }}">{{ __('pc.ref_no_col') }}</div><div style="{{ $val }}">{{ $record->ref_no }}</div></div>
            <div><div style="{{ $lbl }}">{{ __('pc.patient_col') }}</div><div style="{{ $val }}">{{ $p->full_name }}</div></div>
            <div><div style="{{ $lbl }}">{{ __('pc.age') }}</div><div style="{{ $val }}">{{ $p->dob?->age ?? '—' }}</div></div>
            <div><div style="{{ $lbl }}">{{ __('pc.status') }}</div><div style="margin-top:3px;"><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $st['c'] }};background:{{ $st['bg'] }};">{{ $st['label'] }}</span></div></div>
            <div><div style="{{ $lbl }}">{{ __('pc.result_col') }}</div><div style="margin-top:3px;"><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $res['c'] }};background:{{ $res['bg'] }};">{{ $res['label'] }}</span></div></div>
            <div style="grid-column:1 / -1;display:flex;gap:26px;border-top:1px solid #F3E7EE;padding-top:14px;flex-wrap:wrap;">
                <div><div style="{{ $lbl }}">{{ __('pc.venue') }}</div><div style="{{ $val }}">{{ $record->clinic?->name ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.registered_by') }}</div><div style="{{ $val }}">{{ $p->registeredBy?->name ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.nurse_assign') }}</div><div style="{{ $val }}">{{ $record->nurse?->name ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.doctor_col') }}</div><div style="{{ $val }}">{{ $record->doctor?->name ?? '—' }}</div></div>
            </div>
        </div>

        {{-- Contact & ID --}}
        <div style="{{ $card }}padding:20px 24px;">
            {!! $secHead(__('pc.sec_reg')) !!}
            <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:14px;">
                <div><div style="{{ $lbl }}">{{ __('pc.emirates_id') }}</div><div style="{{ $val }}">{{ $p->emirates_id ?: '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.pc_number') }}</div><div style="{{ $val }}">{{ $p->manual_pc_number ?: '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.dob') }}</div><div style="{{ $val }}">{{ optional($p->dob)->format('d M Y') ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.nationality') }}</div><div style="{{ $val }}">{{ $p->nationality ?: '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.emirate') }}</div><div style="{{ $val }}">{{ $p->emirate ? __('pc.em_'.$p->emirate) : '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.marital') }}</div><div style="{{ $val }}">{{ $p->marital_status ? __('pc.'.$p->marital_status) : '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.mobile1') }}</div><div style="{{ $val }}">{{ $p->mobile1 ?: '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.mobile2') }}</div><div style="{{ $val }}">{{ $p->mobile2 ?: '—' }}</div></div>
                <div style="grid-column:1 / -1;"><div style="{{ $lbl }}">{{ __('pc.email') }}</div><div style="{{ $val }}">{{ $p->email ?: '—' }}</div></div>
            </div>
        </div>

        {{-- Reproductive & screening history --}}
        <div style="{{ $card }}padding:20px 24px;">
            {!! $secHead(__('pc.sec_repro')) !!}
            <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:14px;">
                <div><div style="{{ $lbl }}">{{ __('pc.menarche') }}</div><div style="{{ $val }}">{{ $record->age_at_menarche ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.lmp') }}</div><div style="{{ $val }}">{{ optional($record->lmp)->format('d M Y') ?? '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.breast_implant') }}</div><div style="{{ $val }}">{{ $record->breast_implant ? __('pc.'.$record->breast_implant) : '—' }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.last_mammogram') }}</div><div style="{{ $val }}">{{ optional($record->last_mammogram)->format('d M Y') ?? '—' }}</div></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
                <div>
                    <div style="{{ $lbl }}">{{ __('pc.sec_personal') }}</div>
                    @if (count($positiveHistory) === 0)
                        <div style="{{ $val }}color:#9A8F97;">{{ __('pc.no_history') }}</div>
                    @else
                        <ul style="margin:6px 0 0;padding-inline-start:16px;">
                            @foreach ($positiveHistory as $k)
                                <li style="font-size:13px;color:#453A44;margin-bottom:3px;">{{ __('pc.'.$k) }}@if (!empty($phn[$k]))<span style="color:#9A8F97;"> — {{ $phn[$k] }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <div style="{{ $lbl }}">{{ __('pc.fam_hist_bc') }}</div>
                    @php($famRows = collect($degLabels)->filter(fn ($l, $k) => !empty($fh[$k]['relationship']) || !empty($fh[$k]['age'])))
                    @if ($famRows->isEmpty())
                        <div style="{{ $val }}color:#9A8F97;">{{ __('pc.no_history') }}</div>
                    @else
                        <ul style="margin:6px 0 0;padding-inline-start:16px;">
                            @foreach ($famRows as $k => $l)
                                <li style="font-size:13px;color:#453A44;margin-bottom:3px;">{{ $l }}: {{ $fh[$k]['relationship'] ?? '—' }}@if (!empty($fh[$k]['age'])) · {{ __('pc.age_at_diagnosis') }} {{ $fh[$k]['age'] }}@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Consent & signature --}}
        <div style="{{ $card }}padding:20px 24px;">
            {!! $secHead(__('pc.sec_consent')) !!}
            <div style="display:flex;gap:32px;align-items:flex-start;flex-wrap:wrap;">
                <div><div style="{{ $lbl }}">{{ __('pc.status') }}</div><div style="{{ $val }}">{{ $record->consent_given ? __('pc.yes') : __('pc.no') }}</div></div>
                <div><div style="{{ $lbl }}">{{ __('pc.signed_at') }}</div><div style="{{ $val }}">{{ optional($record->signed_at)->format('d M Y') ?? '—' }}</div></div>
                @if ($record->patient_signature)
                    <div><div style="{{ $lbl }}">{{ __('pc.signature') }}</div><img src="{{ $record->patient_signature }}" alt="signature" style="margin-top:4px;height:70px;border:1px solid #EFE2EA;border-radius:8px;background:#fff;" /></div>
                @endif
            </div>
        </div>

        {{-- Clinical examination (if the doctor has completed it) --}}
        @if ($ex && $ex->status === 'submitted')
            <div style="{{ $card }}padding:20px 24px;">
                {!! $secHead(__('pc.clinical_exam_result')) !!}
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:14px;">
                    <div><div style="{{ $lbl }}">{{ __('pc.cbe_result_label') }}</div><div style="{{ $val }}">{{ $ex->cbe_result ? __('pc.'.$ex->cbe_result) : '—' }}</div></div>
                    <div><div style="{{ $lbl }}">{{ __('pc.examined_by') }}</div><div style="{{ $val }}">{{ $record->doctor?->name ?? $ex->examiner_name ?? '—' }}</div></div>
                    <div><div style="{{ $lbl }}">{{ __('pc.recommendation') }}</div><div style="{{ $val }}">{{ $ex->recommendation ?: '—' }}</div></div>
                </div>
                @if ($ex->comments)
                    <div style="margin-top:12px;"><div style="{{ $lbl }}">{{ __('pc.comments') }}</div><div style="{{ $val }}">{{ $ex->comments }}</div></div>
                @endif
                @if ($record->report)
                    <div style="margin-top:16px;">
                        <a href="{{ route('reports.document', $record) }}" target="_blank" style="display:inline-block;background:#FCEFF5;color:#C0116E;font-weight:700;font-size:13px;padding:10px 18px;border-radius:10px;text-decoration:none;">📄 {{ __('pc.view_report') }}</a>
                    </div>
                @endif
            </div>
        @else
            <div style="{{ $card }}padding:18px 24px;color:#9A8F97;font-size:13.5px;">{{ __('pc.not_examined_yet') }}</div>
        @endif
    </div>
</x-staff-shell>
@endsection
