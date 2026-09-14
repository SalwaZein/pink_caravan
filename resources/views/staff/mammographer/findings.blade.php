@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.findings_title'))

@php
    $inp  = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $area = $inp.'min-height:170px;line-height:1.55;resize:vertical;font-family:inherit;';
    $lbl  = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 26px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $ro   = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #EFE2EA;border-radius:9px;font-size:13.5px;background:#FAF4F7;color:#6B4257;font-weight:600;';
    $sectionHead = fn($n, $title) => '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;"><div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">'.$n.'</div><h3 style="margin:0;font-size:16px;font-weight:700;">'.$title.'</h3></div>';
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:820px;margin:0 auto;padding-bottom:30px;">
        <a href="{{ route('mammographer.record', $record) }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.back_to_file') }}</a>

        @if (session('status'))
            <div style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="margin-bottom:14px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
                <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($readOnly)
            <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;background:#E4F4EF;border:1px solid #BFE6D5;color:#2E7D32;font-size:13px;font-weight:600;padding:12px 18px;border-radius:12px;">
                <span>🔒</span><span>{{ __('pc.findings_locked') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('mammographer.findings.save', $record) }}">
            @csrf @method('PUT')
            <fieldset @disabled($readOnly) style="border:0;padding:0;margin:0;min-width:0;">

            {{-- 1. Who this study belongs to. Identity comes from registration; the PC
                 number is the one field the mammographer enters by hand. --}}
            <div style="{{ $card }}">
                {!! $sectionHead(1, __('pc.findings_sec_patient')) !!}
                <div class="pc-cols-2" style="gap:14px;">
                    <label style="{{ $lbl }}">{{ __('pc.patient_number') }}
                        <input value="{{ $patient->pc_number ?? $record->ref_no }}" readonly style="{{ $ro }}" />
                    </label>
                    <label style="{{ $lbl }}">{{ __('pc.full_name') }}
                        <input value="{{ $patient->full_name }}" readonly style="{{ $ro }}" />
                    </label>
                    <label style="{{ $lbl }}">{{ __('pc.email') }}
                        <input value="{{ $patient->email ?: '—' }}" readonly style="{{ $ro }}" />
                    </label>
                    <label style="{{ $lbl }}">{{ __('pc.pc_number') }} *
                        <input name="manual_pc_number" value="{{ old('manual_pc_number', $patient->manual_pc_number) }}"
                               placeholder="{{ __('pc.pc_number_ph') }}" required style="{{ $inp }}" />
                        <span style="display:block;margin-top:5px;font-size:11px;color:#9A8F97;font-weight:500;">{{ __('pc.pc_number_manual_help') }}</span>
                    </label>
                </div>
            </div>

            {{-- 2. The findings, in the mammographer's own words. --}}
            <div style="{{ $card }}">
                {!! $sectionHead(2, __('pc.findings_sec_findings')) !!}
                <label style="{{ $lbl }}display:block;">{{ __('pc.findings_description') }} *
                    <textarea name="findings" rows="8" required style="{{ $area }}" placeholder="{{ __('pc.findings_description_ph') }}">{{ old('findings', $finding->findings) }}</textarea>
                </label>
            </div>

            {{-- 3. Hand the study to a radiologist to report on. --}}
            <div style="{{ $card }}">
                {!! $sectionHead(3, __('pc.findings_sec_assign')) !!}
                <p style="margin:-8px 0 14px;font-size:13px;color:#6B6472;line-height:1.5;">{{ __('pc.findings_assign_hint') }}</p>
                <label style="{{ $lbl }}max-width:420px;display:block;">{{ __('pc.assign_to') }} *
                    <select name="radiologist_id" required style="{{ $inp }}background:#fff;">
                        <option value="">— {{ __('pc.select_radiologist') }} —</option>
                        @foreach ($radiologists as $r)
                            <option value="{{ $r->id }}" @selected(old('radiologist_id', $record->radiologist_id) == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($radiologists->isEmpty())
                    <p style="font-size:12px;color:#C62828;margin:9px 0 0;">{{ __('pc.no_radiologist_in_clinic') }}</p>
                @endif
            </div>

            </fieldset>

            @if ($readOnly)
                <div style="display:flex;justify-content:flex-end;">
                    <a href="{{ route('mammographer.record', $record) }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;font-size:14px;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;background:#fff;text-decoration:none;">{{ __('pc.back') }}</a>
                </div>
            @else
                <div style="display:flex;justify-content:flex-end;gap:12px;">
                    {{-- A draft is partial by definition, so it skips the required-field checks. --}}
                    <button type="submit" name="action" value="draft" formnovalidate style="cursor:pointer;background:#fff;color:#6B4257;font-weight:700;font-size:14px;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;">{{ __('pc.save_draft') }}</button>
                    <button type="submit" name="action" value="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;font-size:14px;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">{{ __('pc.findings_submit') }} →</button>
                </div>
            @endif
        </form>
    </div>
</x-staff-shell>
@endsection
