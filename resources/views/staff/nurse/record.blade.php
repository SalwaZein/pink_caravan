@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.rec_title'))

@php
    $editing = $record->exists;
    $ph  = $record->personal_history ?? [];
    $phn = $record->personal_history_notes ?? [];
    $fh  = $record->family_history ?? [];
    $inp = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $lbl = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:24px 26px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $sectionHead = fn($n, $title) => '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;"><div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">'.$n.'</div><h3 style="margin:0;font-size:16px;font-weight:700;">'.$title.'</h3></div>';
    $personalItems = ['lumpectomy','biopsy','hyperplasia','hrt','personal_bc','ovarian','fam_ovarian','fam_male_bc','implant'];
    $emirates = ['abu_dhabi','dubai','sharjah','ajman','umm_al_quwain','ras_al_khaimah','fujairah'];
    // Family-history relationship options per degree (from business feedback).
    $relOptions = ['deg1' => __('pc.rel_deg1'), 'deg2' => __('pc.rel_deg2'), 'deg3' => __('pc.rel_deg3')];
    $referral = $record->referrals ?? collect();
    $rMammo = $editing ? $referral->firstWhere('type','mammogram') : null;
    $rUls   = $editing ? $referral->firstWhere('type','uls') : null;
    // Emirates ID reader endpoint: the configured local bridge, or the dev mock.
    $eidReaderUrl = config('services.emirates_id.reader_url') ?: route('tools.eid.read');
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    @if ($errors->any())
        <div class="pc-anim" style="max-width:900px;margin:0 auto 12px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
            <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('nurse.record.update', $record) : route('nurse.record.store') }}"
          x-data="{
              result: '{{ old('cbe_result', $record->cbe_result ?? '') }}',
              consent: {{ old('consent', $record->consent_given) ? 'true' : 'false' }},
              hasSignature: {{ $record->patient_signature ? 'true' : 'false' }},
              drawing: false, sigCtx: null,
              eidLoading: false, eidError: '', eidMsg: '',
              async readEid() {
                  this.eidError = ''; this.eidMsg = ''; this.eidLoading = true;
                  try {
                      const res = await fetch('{{ $eidReaderUrl }}', { headers: { 'Accept': 'application/json' } });
                      if (! res.ok) throw new Error('reader');
                      const json = await res.json();
                      if (json.success === false) throw new Error('reader');
                      const d = json.data || json;
                      const root = this.$root;
                      const set = (name, val) => { const el = root.querySelector('[name=\'' + name + '\']'); if (el && val) { el.value = val; el.dispatchEvent(new Event('input', { bubbles: true })); } };
                      set('full_name', d.full_name_en || d.full_name);
                      set('dob', d.date_of_birth || d.dob);
                      set('emirates_id', d.emirates_id || d.eid);
                      if (d.nationality) {
                          const sel = root.querySelector('[name=\'nationality\']');
                          if (sel) { for (const o of sel.options) { if (o.value.toLowerCase() === String(d.nationality).toLowerCase()) { sel.value = o.value; break; } } }
                      }
                      this.eidMsg = '{{ __('pc.eid_filled') }}';
                  } catch (e) {
                      this.eidError = '{{ __('pc.eid_error') }}';
                  } finally {
                      this.eidLoading = false;
                  }
              },
              initSig(canvas) {
                  canvas.width = canvas.clientWidth; canvas.height = 150;
                  const ctx = canvas.getContext('2d');
                  ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#2A2230';
                  this.sigCtx = ctx;
                  const existing = document.getElementById('sig_input').value;
                  if (existing) { const img = new Image(); img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height); img.src = existing; }
                  const pos = (e) => { const r = canvas.getBoundingClientRect(); const t = e.touches ? e.touches[0] : e; return { x: t.clientX - r.left, y: t.clientY - r.top }; };
                  const start = (e) => { this.drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); };
                  const move = (e) => { if (!this.drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); };
                  const end = () => { if (this.drawing) { this.drawing = false; this.hasSignature = true; document.getElementById('sig_input').value = canvas.toDataURL('image/png'); } };
                  canvas.addEventListener('mousedown', start); canvas.addEventListener('mousemove', move); window.addEventListener('mouseup', end);
                  canvas.addEventListener('touchstart', start); canvas.addEventListener('touchmove', move); canvas.addEventListener('touchend', end);
              },
              clearSig(canvas) { this.sigCtx.clearRect(0, 0, canvas.width, canvas.height); this.hasSignature = false; document.getElementById('sig_input').value = ''; }
          }"
          class="pc-anim" style="max-width:900px;margin:0 auto;padding-bottom:30px;">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- 1. Registration --}}
        <div style="{{ $card }}">
            {!! $sectionHead(1, __('pc.sec_reg')) !!}
            {{-- Emirates ID reader: reads the card (via the local bridge / dev mock) and auto-fills demographics. --}}
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-bottom:14px;">
                <span x-show="eidMsg" x-cloak style="font-size:12px;font-weight:600;color:#2E7D32;" x-text="eidMsg"></span>
                <span x-show="eidError" x-cloak style="font-size:12px;font-weight:600;color:#C62828;" x-text="eidError"></span>
                <button type="button" @click="readEid()" :disabled="eidLoading"
                        style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:#fff;color:#6B4257;font-weight:700;font-size:13px;padding:9px 16px;border:1.5px solid #E3D2DC;border-radius:10px;"
                        :style="eidLoading ? { opacity:'0.6', cursor:'wait' } : { opacity:'1', cursor:'pointer' }">
                    <span>🪪</span>
                    <span x-show="!eidLoading">{{ __('pc.read_eid') }}</span>
                    <span x-show="eidLoading" x-cloak>{{ __('pc.reading_card') }}</span>
                </button>
            </div>
            <div class="pc-cols-3" style="gap:14px;">
                {{-- Auto registration number (was "PC Number") --}}
                <label style="{{ $lbl }}">{{ __('pc.reg_number') }}<input value="{{ $editing ? $record->ref_no : __('pc.pc_auto') }}" readonly style="{{ $inp }}background:#FAF4F7;color:#9A8F97;" /></label>
                {{-- Manual PC Number is entered later by the mammographer, so it is not shown in the nurse form. --}}
                {{-- Emirates ID (auto-filled by the reader, or entered manually) --}}
                <label style="{{ $lbl }}">{{ __('pc.emirates_id') }}<input name="emirates_id" value="{{ old('emirates_id', $patient->emirates_id) }}" placeholder="784-____-_______-_" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.full_name') }} *<input name="full_name" value="{{ old('full_name', $patient->full_name) }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.dob') }}<input type="date" name="dob" value="{{ old('dob', optional($patient->dob)->toDateString()) }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.nationality') }}<select name="nationality" style="{{ $inp }}background:#fff;"><option value="">—</option>@foreach (\App\Support\Nationalities::all() as $nat)<option value="{{ $nat }}" @selected(old('nationality', $patient->nationality) === $nat)>{{ $nat }}</option>@endforeach</select></label>
                <label style="{{ $lbl }}">{{ __('pc.emirate') }}<select name="emirate" style="{{ $inp }}background:#fff;"><option value="">—</option>@foreach ($emirates as $em)<option value="{{ $em }}" @selected(old('emirate', $patient->emirate) === $em)>{{ __('pc.em_'.$em) }}</option>@endforeach</select></label>
                <div style="{{ $lbl }}">{{ __('pc.marital') }}
                    <div style="display:flex;gap:16px;margin-top:9px;">
                        @foreach (['single'=>__('pc.single'),'married'=>__('pc.married'),'widow'=>__('pc.widow')] as $val=>$label)
                            <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:500;color:#453A44;">
                                <input type="radio" name="marital_status" value="{{ $val }}" @checked(old('marital_status', $patient->marital_status) === $val) style="width:16px;height:16px;accent-color:#E6017E;margin:0;cursor:pointer;" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <label style="{{ $lbl }}">{{ __('pc.mobile1') }} *<input type="tel" name="mobile1" value="{{ old('mobile1', $patient->mobile1) }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.mobile2') }}<input type="tel" name="mobile2" value="{{ old('mobile2', $patient->mobile2) }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.email') }}<input type="email" name="email" value="{{ old('email', $patient->email) }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.menarche') }}<input type="number" name="age_at_menarche" value="{{ old('age_at_menarche', $record->age_at_menarche) }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.lmp') }}<input type="date" name="lmp" value="{{ old('lmp', optional($record->lmp)->toDateString()) }}" style="{{ $inp }}" /></label>
                {{-- Breast implant Yes/No — replaces "Number of children", last field in this section --}}
                <div style="{{ $lbl }}">{{ __('pc.breast_implant') }}
                    <div style="display:flex;gap:16px;margin-top:9px;">
                        @foreach (['yes'=>__('pc.yes'),'no'=>__('pc.no')] as $val=>$label)
                            <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:500;color:#453A44;">
                                <input type="radio" name="breast_implant" value="{{ $val }}" @checked(old('breast_implant', $record->breast_implant) === $val) style="width:16px;height:16px;accent-color:#E6017E;margin:0;cursor:pointer;" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Personal history --}}
        <div style="{{ $card }}">
            {!! $sectionHead(2, __('pc.sec_personal')) !!}
            <div class="pc-cols-2" style="gap:6px 24px;">
                @foreach ($personalItems as $pi)
                    @php($ansOld = old("personal.$pi", $ph[$pi] ?? 'no'))
                    @php($noteOld = old("personal_notes.$pi", $phn[$pi] ?? ''))
                    <div x-data="{ v: '{{ $ansOld }}' }" style="padding:9px 0;border-bottom:1px solid #F3E7EE;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <span style="font-size:13.5px;color:#453A44;">{{ __('pc.'.$pi) }}</span>
                            <input type="hidden" name="personal[{{ $pi }}]" :value="v" />
                            <div style="display:flex;gap:8px;flex-shrink:0;">
                                <span @click="v='yes'" role="button"
                                      style="cursor:pointer;min-width:54px;text-align:center;padding:6px 18px;border:1.5px solid #DCC9D5;border-radius:999px;font-size:12.5px;background:#fff;color:#9A8F97;"
                                      :style="'cursor:pointer;min-width:54px;text-align:center;padding:6px 18px;border-radius:999px;font-size:12.5px;border:1.5px solid;transition:all .12s;' + (v==='yes' ? 'background:#FCEFF5;border-color:#E6017E;color:#E6017E;font-weight:700;' : 'background:#fff;border-color:#DCC9D5;color:#9A8F97;font-weight:500;')">{{ __('pc.yes') }}</span>
                                <span @click="v='no'" role="button"
                                      style="cursor:pointer;min-width:54px;text-align:center;padding:6px 18px;border:1.5px solid #DCC9D5;border-radius:999px;font-size:12.5px;background:#fff;color:#9A8F97;"
                                      :style="'cursor:pointer;min-width:54px;text-align:center;padding:6px 18px;border-radius:999px;font-size:12.5px;border:1.5px solid;transition:all .12s;' + (v==='no' ? 'background:#F1E7ED;border-color:#6B4257;color:#6B4257;font-weight:700;' : 'background:#fff;border-color:#DCC9D5;color:#9A8F97;font-weight:500;')">{{ __('pc.no') }}</span>
                            </div>
                        </div>
                        <div x-show="v==='yes'" x-cloak style="margin-top:8px;">
                            <textarea name="personal_notes[{{ $pi }}]" rows="3" placeholder="{{ __('pc.details_ph') }}" style="width:100%;padding:11px 13px;border:1px solid #E3D2DC;border-radius:9px;font-size:13px;line-height:1.5;min-height:78px;resize:vertical;">{{ $noteOld }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 3. Family history — one row per degree (relationship + age at diagnosis) --}}
        <div style="{{ $card }}">
            {!! $sectionHead(3, __('pc.fam_hist_bc')) !!}
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach (['deg1'=>__('pc.deg1'),'deg2'=>__('pc.deg2'),'deg3'=>__('pc.deg3')] as $dk=>$dl)
                    @php($fRel = old("family.$dk.relationship", $fh[$dk]['relationship'] ?? ''))
                    @php($fAge = old("family.$dk.age", $fh[$dk]['age'] ?? ''))
                    <div class="pc-stack-sm" style="display:grid;grid-template-columns:96px 1fr 170px;gap:14px;align-items:center;border:1px solid #EFE2EA;border-radius:12px;padding:14px;background:#FAF4F7;">
                        <div style="font-size:13px;font-weight:700;color:#E6017E;">{{ $dl }}</div>
                        <div style="font-size:11.5px;font-weight:600;color:#6B4257;">{{ __('pc.relationship') }}
                            <div style="display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:8px;">
                                @foreach ($relOptions[$dk] as $opt)
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12.5px;font-weight:500;color:#453A44;">
                                        <input type="radio" name="family[{{ $dk }}][relationship]" value="{{ $opt }}" @checked($fRel === $opt) style="width:15px;height:15px;accent-color:#E6017E;margin:0;cursor:pointer;" />
                                        {{ $opt }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <label style="font-size:11.5px;font-weight:600;color:#6B4257;">{{ __('pc.age_at_diagnosis') }}
                            <input type="number" min="0" max="120" name="family[{{ $dk }}][age]" value="{{ $fAge }}" placeholder="—" style="{{ $inp }}" />
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. Previous screening results --}}
        <div style="{{ $card }}">
            {!! $sectionHead(4, __('pc.prev_screening')) !!}
            <input type="hidden" name="cbe_result" :value="result" />
            <div class="pc-cols-2" style="gap:16px;align-items:end;">
                <label style="{{ $lbl }}">{{ __('pc.last_mammo') }}<input type="date" name="last_mammogram" value="{{ old('last_mammogram', optional($record->last_mammogram)->toDateString()) }}" style="{{ $inp }}" /></label>
                <div style="{{ $lbl }}">{{ __('pc.last_mammo_report') }}
                    <div style="display:flex;gap:10px;margin-top:7px;">
                        <span @click="result='normal'" role="button" style="cursor:pointer;flex:1;text-align:center;padding:10px;border:2px solid #E3D2DC;border-radius:9px;font-size:13px;font-weight:700;background:#fff;color:#6B6472;" :style="result==='normal' ? { borderColor:'#2E7D32', background:'#E4F4EF', color:'#2E7D32' } : { borderColor:'#E3D2DC', background:'#fff', color:'#6B6472' }">{{ __('pc.normal') }}</span>
                        <span @click="result='abnormal'" role="button" style="cursor:pointer;flex:1;text-align:center;padding:10px;border:2px solid #E3D2DC;border-radius:9px;font-size:13px;font-weight:700;background:#fff;color:#6B6472;" :style="result==='abnormal' ? { borderColor:'#C62828', background:'#FBE4E4', color:'#C62828' } : { borderColor:'#E3D2DC', background:'#fff', color:'#6B6472' }">{{ __('pc.abnormal') }}</span>
                    </div>
                </div>
            </div>
            <div x-show="result==='abnormal'" x-cloak class="pc-stack-sm" style="margin-top:16px;padding:16px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <label style="font-size:12.5px;font-weight:600;color:#9A2E2E;">{{ __('pc.refer_mammo') }}<input type="date" name="refer_mammo_date" value="{{ old('refer_mammo_date', optional($rMammo?->referral_date)->toDateString()) }}" style="{{ $inp }}border-color:#E7B7B7;" /></label>
                <label style="font-size:12.5px;font-weight:600;color:#9A2E2E;">{{ __('pc.hospital') }}<input name="refer_mammo_hospital" value="{{ old('refer_mammo_hospital', $rMammo?->hospital) }}" style="{{ $inp }}border-color:#E7B7B7;" /></label>
                <label style="font-size:12.5px;font-weight:600;color:#9A2E2E;">{{ __('pc.refer_uls') }}<input type="date" name="refer_uls_date" value="{{ old('refer_uls_date', optional($rUls?->referral_date)->toDateString()) }}" style="{{ $inp }}border-color:#E7B7B7;" /></label>
                <label style="font-size:12.5px;font-weight:600;color:#9A2E2E;">{{ __('pc.hospital') }}<input name="refer_uls_hospital" value="{{ old('refer_uls_hospital', $rUls?->hospital) }}" style="{{ $inp }}border-color:#E7B7B7;" /></label>
            </div>
        </div>

        {{-- 5. Consent + patient signature --}}
        <div style="{{ $card }}">
            {!! $sectionHead(5, __('pc.consent_title')) !!}
            <div style="background:#FAF4F7;border-radius:12px;padding:16px 18px;margin-bottom:16px;">
                @foreach (__('pc.consent_statements') as $cs)
                    <div style="display:flex;gap:8px;font-size:13px;color:#453A44;margin-bottom:7px;line-height:1.45;"><span style="color:#E6017E;">•</span><span>{{ $cs }}</span></div>
                @endforeach
            </div>
            <label style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:13px 16px;border:2px solid #E3D2DC;border-radius:12px;background:#fff;">
                <input type="checkbox" name="consent" value="1" x-model="consent" style="width:20px;height:20px;accent-color:#E6017E;margin:0;cursor:pointer;flex-shrink:0;" />
                <span style="font-size:13.5px;font-weight:600;color:#453A44;">{{ __('pc.consent_ack') }}</span>
            </label>

            {{-- Patient signature (required before submission) --}}
            <input type="hidden" name="patient_signature" id="sig_input" value="{{ old('patient_signature', $record->patient_signature) }}" />
            <div class="pc-stack-sm" style="margin-top:18px;display:grid;grid-template-columns:1fr 200px;gap:16px;align-items:start;">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="{{ $lbl }}">{{ __('pc.sign_here') }} *</span>
                        <span @click="clearSig($refs.sig)" role="button" style="cursor:pointer;font-size:12px;font-weight:600;color:#E6017E;">{{ __('pc.clear_signature') }}</span>
                    </div>
                    <canvas x-ref="sig" x-init="initSig($refs.sig)" style="width:100%;height:150px;border:1.5px dashed #D8C4CF;border-radius:12px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                    <p style="font-size:11px;color:#9A8F97;margin:6px 0 0;">{{ __('pc.sign_hint') }}</p>
                </div>
                <label style="{{ $lbl }}">{{ __('pc.sign_date') }}<input type="date" name="signed_at" value="{{ old('signed_at', optional($record->signed_at)->toDateString() ?? now()->toDateString()) }}" style="{{ $inp }}" /></label>
            </div>
        </div>

        {{-- Sticky footer --}}
        <div style="position:sticky;bottom:0;background:linear-gradient(0deg,#F4EEF1 70%,transparent);padding:14px 0 4px;display:flex;justify-content:space-between;align-items:center;">
            <button type="submit" name="action" value="draft" style="cursor:pointer;color:#6B6472;font-weight:600;font-size:14px;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;background:#fff;">{{ __('pc.save_draft') }}</button>
            <div style="display:flex;align-items:center;gap:14px;">
                <span x-show="!consent" style="font-size:12.5px;color:#C62828;">{{ __('pc.consent_required') }}</span>
                <span x-show="consent && !hasSignature" x-cloak style="font-size:12.5px;color:#C62828;">{{ __('pc.signature_required') }}</span>
                <button type="submit" name="action" value="submit" :disabled="!consent || !hasSignature" style="cursor:pointer;color:#fff;font-weight:700;font-size:14px;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);background:#E3D2DC;" :style="(consent && hasSignature) ? { background:'linear-gradient(90deg,#E6017E,#C0116E)', cursor:'pointer' } : { background:'#E3D2DC', cursor:'not-allowed' }">{{ __('pc.submit_assign') }} →</button>
            </div>
        </div>
    </form>
</x-staff-shell>
@endsection
