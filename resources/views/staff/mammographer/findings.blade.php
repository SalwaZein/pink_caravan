@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.findings_title'))

@php
    use App\Models\MammogramFinding;

    $inp  = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $area = $inp.'min-height:96px;line-height:1.5;resize:vertical;font-family:inherit;';
    $lbl  = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 26px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $sectionHead = fn($n, $title) => '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;"><div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">'.$n.'</div><h3 style="margin:0;font-size:16px;font-weight:700;">'.$title.'</h3></div>';

    $old = fn (string $k, $fallback = null) => old($k, $fallback);
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:900px;margin:0 auto;padding-bottom:30px;">
        <a href="{{ route('mammographer.record', $record) }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.back_to_file') }}</a>

        @if (session('status'))
            <div style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="margin-bottom:14px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
                <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Whose file this is. --}}
        <div style="{{ $card }}display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;" class="pc-stack-sm">
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.reg_number') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->ref_no }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.patient_col') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->full_name }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.pc_number') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $patient->manual_pc_number ?: '—' }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.status') }}</div>
                <div style="margin-top:3px;"><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $finding->isSubmitted() ? '#2E7D32' : '#6B4257' }};background:{{ $finding->isSubmitted() ? '#E4F4EF' : '#F1E7ED' }};">{{ $finding->isSubmitted() ? __('pc.findings_submitted') : __('pc.draft') }}</span></div>
            </div>
        </div>

        @if ($readOnly)
            <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;background:#E4F4EF;border:1px solid #BFE6D5;color:#2E7D32;font-size:13px;font-weight:600;padding:12px 18px;border-radius:12px;">
                <span>🔒</span><span>{{ __('pc.findings_locked') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('mammographer.findings.save', $record) }}">
            @csrf @method('PUT')
            <fieldset @disabled($readOnly) style="border:0;padding:0;margin:0;min-width:0;">

            {{-- 1. Study --}}
            <div style="{{ $card }}">
                {!! $sectionHead(1, __('pc.findings_sec_study')) !!}
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;" class="pc-stack-sm">
                    <label style="{{ $lbl }}">{{ __('pc.findings_exam_date') }}
                        <x-date-field name="exam_date" :value="old('exam_date', optional($finding->exam_date)->toDateString() ?? now()->toDateString())" :min-year="now()->year - 2" :max-year="now()->year" />
                    </label>
                    <label style="{{ $lbl }}">{{ __('pc.findings_modality') }}
                        <select name="modality" style="{{ $inp }}">
                            <option value="">—</option>
                            @foreach (MammogramFinding::MODALITIES as $m)
                                <option value="{{ $m }}" @selected($old('modality', $finding->modality) === $m)>{{ __('pc.modality_'.$m) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="{{ $lbl }}">{{ __('pc.findings_density') }}
                        <select name="breast_density" style="{{ $inp }}">
                            <option value="">—</option>
                            @foreach (MammogramFinding::DENSITIES as $d)
                                <option value="{{ $d }}" @selected($old('breast_density', $finding->breast_density) === $d)>{{ __('pc.density_'.$d) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label style="{{ $lbl }}display:block;margin-top:16px;">{{ __('pc.findings_comparison') }}
                    <textarea name="comparison" rows="2" style="{{ $area }}min-height:64px;" placeholder="{{ __('pc.findings_comparison_ph') }}">{{ $old('comparison', $finding->comparison) }}</textarea>
                </label>
            </div>

            {{-- 2. Per-side findings --}}
            <div style="{{ $card }}">
                {!! $sectionHead(2, __('pc.findings_sec_sides')) !!}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="pc-stack-sm">
                    @foreach ([['right', __('pc.side_right')], ['left', __('pc.side_left')]] as [$side, $sideLabel])
                        <div style="border:1px solid #F3E7EE;border-radius:12px;padding:16px;">
                            <div style="font-size:13.5px;font-weight:700;color:#6B4257;margin-bottom:12px;">{{ $sideLabel }}</div>
                            <label style="{{ $lbl }}">{{ __('pc.findings_birads') }}
                                <select name="birads_{{ $side }}" style="{{ $inp }}">
                                    <option value="">—</option>
                                    @foreach (MammogramFinding::BIRADS as $b)
                                        <option value="{{ $b }}" @selected($old('birads_'.$side, $finding->{'birads_'.$side}) === $b)>{{ __('pc.birads_'.$b) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label style="{{ $lbl }}display:block;margin-top:12px;">{{ __('pc.findings_description') }}
                                <textarea name="findings_{{ $side }}" rows="4" style="{{ $area }}" placeholder="{{ __('pc.findings_description_ph') }}">{{ $old('findings_'.$side, $finding->{'findings_'.$side}) }}</textarea>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 3. Impression + recommendation --}}
            <div style="{{ $card }}">
                {!! $sectionHead(3, __('pc.findings_sec_impression')) !!}
                <label style="{{ $lbl }}display:block;">{{ __('pc.findings_impression') }}
                    <textarea name="impression" rows="4" style="{{ $area }}" placeholder="{{ __('pc.findings_impression_ph') }}">{{ $old('impression', $finding->impression) }}</textarea>
                </label>
                <label style="{{ $lbl }}display:block;margin-top:16px;">{{ __('pc.findings_recommendation') }}
                    <select name="recommendation" style="{{ $inp }}">
                        <option value="">—</option>
                        @foreach (MammogramFinding::RECOMMENDATIONS as $r)
                            <option value="{{ $r }}" @selected($old('recommendation', $finding->recommendation) === $r)>{{ __('pc.mrec_'.$r) }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="{{ $lbl }}display:block;margin-top:16px;">{{ __('pc.findings_notes') }}
                    <textarea name="notes" rows="3" style="{{ $area }}min-height:76px;">{{ $old('notes', $finding->notes) }}</textarea>
                </label>
            </div>

            </fieldset>

            @if ($readOnly)
                <div style="display:flex;justify-content:flex-end;">
                    <a href="{{ route('mammographer.record', $record) }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;font-size:14px;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;background:#fff;text-decoration:none;">{{ __('pc.back') }}</a>
                </div>
            @else
                <div style="display:flex;justify-content:flex-end;gap:12px;">
                    <button type="submit" name="action" value="draft" style="cursor:pointer;background:#fff;color:#6B4257;font-weight:700;font-size:14px;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;">{{ __('pc.save_draft') }}</button>
                    <button type="submit" name="action" value="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;font-size:14px;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">{{ __('pc.findings_submit') }}</button>
                </div>
            @endif
        </form>
    </div>
</x-staff-shell>
@endsection
