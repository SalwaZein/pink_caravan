@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.exam_header'))

@php
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $symList = __('pc.symptom_list');
    $signList = __('pc.sign_list');
    $exSym = $exam->symptoms ?? [];
    $exSign = $exam->signs ?? [];
    $on = fn($arr, $i, $s) => ! empty(($arr[$i] ?? [])[$s] ?? null);
    $pinsJson = json_encode($exam->pins ?? []);
    $cbStyle = 'width:18px;height:18px;accent-color:#E6017E;margin:0;cursor:pointer;';

    // ---- Form 1 (registration) summary data for the doctor to review ----
    $patient = $record->patient;
    $ph  = $record->personal_history ?? [];
    $phn = $record->personal_history_notes ?? [];
    $fh  = $record->family_history ?? [];
    $personalItems = ['lumpectomy','biopsy','hyperplasia','hrt','personal_bc','ovarian','fam_ovarian','fam_male_bc','implant'];
    $positiveHistory = array_values(array_filter($personalItems, fn ($k) => ($ph[$k] ?? 'no') === 'yes'));
    $degLabels = ['deg1' => __('pc.deg1'), 'deg2' => __('pc.deg2'), 'deg3' => __('pc.deg3')];
    $sumLbl = 'font-size:11px;color:#9A8F97;font-weight:600;';
    $sumVal = 'font-size:13.5px;font-weight:600;color:#2A2230;margin-top:2px;';
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <form method="POST" action="{{ route('doctor.exam.update', $record) }}"
          x-data="{ pins: {{ $pinsJson }},
                    cbe: '{{ old('cbe_result', $exam->cbe_result ?? '') }}',
                    showForm1: true,
                    addPin(e){ const r=e.currentTarget.getBoundingClientRect(); this.pins.push({x:+(((e.clientX-r.left)/r.width*100).toFixed(1)), y:+(((e.clientY-r.top)/r.height*100).toFixed(1))}); } }"
          class="pc-anim" style="max-width:980px;margin:0 auto;padding-bottom:30px;">
        @csrf @method('PUT')
        <input type="hidden" name="pins" :value="JSON.stringify(pins)" />
        <input type="hidden" name="cbe_result" :value="cbe" />

        {{-- Header (pulled from the patient record) --}}
        <div class="pc-cols-5" style="{{ $card }}padding:20px 24px;margin-bottom:16px;gap:16px;">
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.ref_no_col') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->ref_no }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.patient_col') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->patient->full_name }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.age') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->patient->dob?->age ?? '—' }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">Date</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ now()->format('d M Y') }}</div></div>
            <div><div style="font-size:11px;color:#9A8F97;font-weight:600;">{{ __('pc.venue') }}</div><div style="font-size:14px;font-weight:700;margin-top:2px;">{{ $record->clinic?->name ?? '—' }}</div></div>
        </div>

        {{-- Patient registration (Form 1) — reviewed before the clinical examination --}}
        <div style="{{ $card }}padding:20px 24px;margin-bottom:16px;">
            <div @click="showForm1=!showForm1" role="button" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">1</div>
                    <div>
                        <h3 style="margin:0;font-size:15px;font-weight:700;">{{ __('pc.form1_summary') }}</h3>
                        <p style="margin:2px 0 0;font-size:11.5px;color:#9A8F97;">{{ __('pc.form1_review') }}</p>
                    </div>
                </div>
                <span style="font-size:13px;font-weight:600;color:#E6017E;" x-text="showForm1 ? '−' : '+'"></span>
            </div>

            <div x-show="showForm1" x-cloak style="margin-top:16px;border-top:1px solid #F3E7EE;padding-top:16px;">
                {{-- Demographics + reproductive --}}
                <div class="pc-cols-4" style="gap:14px;margin-bottom:16px;">
                    <div><div style="{{ $sumLbl }}">{{ __('pc.pc_number') }}</div><div style="{{ $sumVal }}">{{ $patient->manual_pc_number ?: '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.nationality') }}</div><div style="{{ $sumVal }}">{{ $patient->nationality ?: '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.marital') }}</div><div style="{{ $sumVal }}">{{ $patient->marital_status ? __('pc.'.$patient->marital_status) : '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.emirate') }}</div><div style="{{ $sumVal }}">{{ $patient->emirate ? __('pc.em_'.$patient->emirate) : '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.menarche') }}</div><div style="{{ $sumVal }}">{{ $record->age_at_menarche ?? '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.lmp') }}</div><div style="{{ $sumVal }}">{{ optional($record->lmp)->format('d M Y') ?? '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.breast_implant') }}</div><div style="{{ $sumVal }}">{{ $record->breast_implant ? __('pc.'.$record->breast_implant) : '—' }}</div></div>
                    <div><div style="{{ $sumLbl }}">{{ __('pc.last_mammo_report') }}</div><div style="{{ $sumVal }}">{{ $record->cbe_result ? __('pc.'.$record->cbe_result) : '—' }}</div></div>
                </div>

                <div class="pc-cols-2" style="gap:16px;">
                    {{-- Personal history (positive findings only) --}}
                    <div>
                        <div style="{{ $sumLbl }}">{{ __('pc.sec_personal') }}</div>
                        @if (count($positiveHistory) === 0)
                            <div style="{{ $sumVal }}color:#9A8F97;">{{ __('pc.no_history') }}</div>
                        @else
                            <ul style="margin:6px 0 0;padding-inline-start:16px;">
                                @foreach ($positiveHistory as $k)
                                    <li style="font-size:13px;color:#453A44;margin-bottom:3px;">{{ __('pc.'.$k) }}@if (!empty($phn[$k]))<span style="color:#9A8F97;"> — {{ $phn[$k] }}</span>@endif</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    {{-- Family history --}}
                    <div>
                        <div style="{{ $sumLbl }}">{{ __('pc.fam_hist_bc') }}</div>
                        @php($famRows = collect($degLabels)->filter(fn ($l, $k) => !empty($fh[$k]['relationship']) || !empty($fh[$k]['age'])))
                        @if ($famRows->isEmpty())
                            <div style="{{ $sumVal }}color:#9A8F97;">{{ __('pc.no_history') }}</div>
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
        </div>

        <div class="pc-cols-2" style="gap:16px;margin-bottom:16px;">
            {{-- Symptoms --}}
            <div style="{{ $card }}padding:20px 22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;"><h3 style="margin:0;font-size:15px;font-weight:700;">{{ __('pc.symptoms') }}</h3><div style="display:flex;gap:16px;font-size:11px;font-weight:700;color:#9A8F97;"><span>{{ __('pc.right') }}</span><span>{{ __('pc.left') }}</span></div></div>
                @foreach ($symList as $i => $label)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #F3E7EE;">
                        <span style="font-size:13px;color:#453A44;">{{ $label }}</span>
                        <div style="display:flex;gap:22px;align-items:center;">
                            <input type="checkbox" name="symptoms[{{ $i }}][R]" value="1" @checked($on($exSym,$i,'R')) style="{{ $cbStyle }}" />
                            <input type="checkbox" name="symptoms[{{ $i }}][L]" value="1" @checked($on($exSym,$i,'L')) style="{{ $cbStyle }}" />
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- Signs --}}
            <div style="{{ $card }}padding:20px 22px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;"><h3 style="margin:0;font-size:15px;font-weight:700;">{{ __('pc.clinical_signs') }}</h3><div style="display:flex;gap:16px;font-size:11px;font-weight:700;color:#9A8F97;"><span>{{ __('pc.right') }}</span><span>{{ __('pc.left') }}</span></div></div>
                @foreach ($signList as $i => $label)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #F3E7EE;">
                        <span style="font-size:13px;color:#453A44;">{{ $label }}</span>
                        <div style="display:flex;gap:22px;align-items:center;">
                            <input type="checkbox" name="signs[{{ $i }}][R]" value="1" @checked($on($exSign,$i,'R')) style="{{ $cbStyle }}" />
                            <input type="checkbox" name="signs[{{ $i }}][L]" value="1" @checked($on($exSign,$i,'L')) style="{{ $cbStyle }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Breast diagram (interactive pins) --}}
        <div style="{{ $card }}padding:20px 24px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;"><h3 style="margin:0;font-size:15px;font-weight:700;">{{ __('pc.breast_diagram') }}</h3><div style="display:flex;align-items:center;gap:14px;"><span style="font-size:12px;color:#9A8F97;"><span x-text="pins.length">0</span> {{ __('pc.pins_placed') }}</span><span @click="pins=[]" role="button" style="cursor:pointer;font-size:12px;font-weight:600;color:#E6017E;">{{ __('pc.clear_pins') }}</span></div></div>
            <p style="font-size:12px;color:#9A8F97;margin:0 0 14px;">{{ __('pc.diagram_help') }}</p>
            {{-- The click layer must be the DIAGRAM box itself, not the padded frame around it:
                 pins are stored as percentages of whatever element is measured, so measuring the
                 frame would make every coordinate depend on the doctor's window width and land in
                 the wrong place on the report. Keep this box and the report's in sync. --}}
            <div style="background:#FBF6F9;border:1px dashed #E3D2DC;border-radius:14px;padding:20px;display:flex;align-items:center;justify-content:center;">
                <div @click="addPin($event)" role="button" style="cursor:crosshair;position:relative;width:420px;max-width:100%;">
                    <svg viewBox="0 0 420 200" style="display:block;width:100%;height:auto;pointer-events:none;">
                        <text x="105" y="24" text-anchor="middle" font-size="13" font-weight="700" fill="#B7A9B2">R</text>
                        <text x="315" y="24" text-anchor="middle" font-size="13" font-weight="700" fill="#B7A9B2">L</text>
                        <circle cx="105" cy="115" r="72" fill="none" stroke="#E0C9D5" stroke-width="2"/>
                        <circle cx="105" cy="115" r="12" fill="none" stroke="#D3A9BF" stroke-width="2"/>
                        <circle cx="315" cy="115" r="72" fill="none" stroke="#E0C9D5" stroke-width="2"/>
                        <circle cx="315" cy="115" r="12" fill="none" stroke="#D3A9BF" stroke-width="2"/>
                        <line x1="210" y1="40" x2="210" y2="190" stroke="#EEDCE6" stroke-width="1" stroke-dasharray="4 4"/>
                    </svg>
                    <template x-for="(pin, idx) in pins" :key="idx">
                        <div :style="`position:absolute;left:${pin.x}%;top:${pin.y}%;transform:translate(-50%,-50%);width:24px;height:24px;border-radius:50%;background:#E6017E;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(230,1,126,.4);border:2px solid #fff;`" x-text="idx+1"></div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Clinical Breast Examination Result (after the breast diagram) --}}
        <div style="{{ $card }}padding:20px 24px;margin-bottom:16px;">
            <h3 style="margin:0 0 12px;font-size:15px;font-weight:700;">{{ __('pc.cbe_exam_result') }}</h3>
            <div style="display:flex;gap:12px;max-width:420px;">
                <span @click="cbe='normal'" role="button" style="cursor:pointer;flex:1;text-align:center;padding:12px;border:2px solid #E3D2DC;border-radius:10px;font-size:14px;font-weight:700;background:#fff;color:#6B6472;" :style="cbe==='normal' ? { borderColor:'#2E7D32', background:'#E4F4EF', color:'#2E7D32' } : { borderColor:'#E3D2DC', background:'#fff', color:'#6B6472' }">{{ __('pc.normal') }}</span>
                <span @click="cbe='abnormal'" role="button" style="cursor:pointer;flex:1;text-align:center;padding:12px;border:2px solid #E3D2DC;border-radius:10px;font-size:14px;font-weight:700;background:#fff;color:#6B6472;" :style="cbe==='abnormal' ? { borderColor:'#C62828', background:'#FBE4E4', color:'#C62828' } : { borderColor:'#E3D2DC', background:'#fff', color:'#6B6472' }">{{ __('pc.abnormal') }}</span>
            </div>
        </div>

        {{-- Comments + recommendation --}}
        <div style="{{ $card }}padding:20px 24px;margin-bottom:16px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#6B4257;margin-bottom:14px;">{{ __('pc.comments') }}<textarea name="comments" rows="3" style="display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:13.5px;resize:vertical;">{{ old('comments', $exam->comments) }}</textarea></label>
            <label style="display:block;font-size:12.5px;font-weight:600;color:#6B4257;margin-bottom:14px;">{{ __('pc.other_findings') }}<textarea name="other_findings" rows="2" style="display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:13.5px;resize:vertical;">{{ old('other_findings', $exam->other_findings) }}</textarea></label>
            <label style="display:block;font-size:12.5px;font-weight:600;color:#6B4257;">{{ __('pc.recommendation') }}<select name="recommendation" style="display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:13.5px;background:#fff;">@foreach ($recoOptions as $opt)<option value="{{ $opt }}" @selected(old('recommendation', $exam->recommendation) === $opt)>{{ $opt }}</option>@endforeach</select></label>
        </div>

        {{-- Sign & submit --}}
        @if ($exam->status === 'submitted')
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px;background:#FBEEDD;border:1px solid #F0D5AE;color:#B25E00;font-size:13px;font-weight:600;padding:12px 18px;border-radius:12px;">
                <span>✎</span><span>{{ __('pc.exam_submitted_notice') }}</span>
            </div>
        @endif
        <div style="{{ $card }}padding:20px 24px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="font-size:13px;color:#6B6472;">{{ __('pc.examiner') }}: <b style="color:#2A2230;">{{ auth()->user()->name }}</b></div>
                <button type="submit" name="action" value="draft" style="cursor:pointer;color:#6B6472;font-weight:600;font-size:13px;padding:10px 18px;border:1px solid #E3D2DC;border-radius:10px;background:#fff;">{{ __('pc.save_draft') }}</button>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <span x-show="!cbe" x-cloak style="font-size:12.5px;color:#C62828;">{{ __('pc.cbe_exam_result') }}?</span>
                <button type="submit" name="action" value="submit" :disabled="!cbe" style="cursor:pointer;color:#fff;font-weight:700;font-size:14px;padding:13px 28px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);background:#E3D2DC;" :style="cbe ? { background:'linear-gradient(90deg,#E6017E,#C0116E)', cursor:'pointer' } : { background:'#E3D2DC', cursor:'not-allowed' }">✍ {{ $exam->status === 'submitted' ? __('pc.update_resubmit') : __('pc.sign_submit') }}</button>
            </div>
        </div>
    </form>
</x-staff-shell>
@endsection
