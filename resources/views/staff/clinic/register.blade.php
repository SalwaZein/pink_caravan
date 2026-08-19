@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_register_patient'))

@php($inp = 'display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:14px;')
@php($lbl = 'font-size:12.5px;font-weight:600;color:#6B4257;')
@php($emirates = ['abu_dhabi','dubai','sharjah','ajman','umm_al_quwain','ras_al_khaimah','fujairah'])

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:720px;margin:0 auto;">
        <form method="POST" action="{{ route('clinic.register.store') }}"
              x-data="{
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
                  }
              }"
              style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            @csrf
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;">
                <p style="margin:0;color:#6B6472;font-size:14px;max-width:60%;">{{ __('pc.register_hint') }}</p>
                {{-- Emirates ID reader: reads the card (via the local bridge / dev mock) and auto-fills demographics. --}}
                <div style="display:flex;align-items:center;gap:10px;">
                    <span x-show="eidMsg" x-cloak style="font-size:12px;font-weight:600;color:#2E7D32;" x-text="eidMsg"></span>
                    <span x-show="eidError" x-cloak style="font-size:12px;font-weight:600;color:#C62828;" x-text="eidError"></span>
                    <button type="button" @click="readEid()" :disabled="eidLoading"
                            style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:#fff;color:#6B4257;font-weight:700;font-size:13px;padding:9px 16px;border:1.5px solid #E3D2DC;border-radius:10px;white-space:nowrap;"
                            :style="eidLoading ? { opacity:'0.6', cursor:'wait' } : { opacity:'1', cursor:'pointer' }">
                        <span>🪪</span>
                        <span x-show="!eidLoading">{{ __('pc.read_eid') }}</span>
                        <span x-show="eidLoading" x-cloak>{{ __('pc.reading_card') }}</span>
                    </button>
                </div>
            </div>

            @if ($errors->any())
                <div style="margin-bottom:16px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:12px 16px;color:#9A2E2E;font-size:13px;">
                    <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <label style="{{ $lbl }}">{{ __('pc.full_name') }} *<input name="full_name" value="{{ old('full_name') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.emirates_id') }}<input name="emirates_id" value="{{ old('emirates_id') }}" placeholder="784-____-_______-_" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.dob') }}<input type="date" name="dob" value="{{ old('dob') }}" style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.nationality') }}<select name="nationality" style="{{ $inp }}background:#fff;"><option value="">—</option>@foreach (\App\Support\Nationalities::all() as $nat)<option value="{{ $nat }}" @selected(old('nationality') === $nat)>{{ $nat }}</option>@endforeach</select></label>
                <label style="{{ $lbl }}">{{ __('pc.mobile1') }} *<input type="tel" name="mobile1" value="{{ old('mobile1') }}" required style="{{ $inp }}" /></label>
                <label style="{{ $lbl }}">{{ __('pc.emirate') }}<select name="emirate" style="{{ $inp }}background:#fff;"><option value="">—</option>@foreach ($emirates as $em)<option value="{{ $em }}" @selected(old('emirate') === $em)>{{ __('pc.em_'.$em) }}</option>@endforeach</select></label>
                <label style="grid-column:1 / -1;{{ $lbl }}">{{ __('pc.nurse_assign') }} *
                    <select name="nurse_id" required style="{{ $inp }}background:#fff;">
                        <option value="">— {{ __('pc.select_nurse') }} —</option>
                        @foreach ($nurses as $n)
                            <option value="{{ $n->id }}" @selected(old('nurse_id') == $n->id)>{{ $n->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:22px;">
                <a href="{{ url('/clinic/queue') }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;text-decoration:none;">{{ __('pc.back') }}</a>
                <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">{{ __('pc.register_add') }} →</button>
            </div>
        </form>
    </div>
</x-staff-shell>
@endsection
