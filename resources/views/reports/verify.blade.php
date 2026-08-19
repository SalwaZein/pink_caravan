@extends('layouts.app')
@section('title', 'Pink Caravan — Report verification')

@section('content')
<style>
  @keyframes vFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .v-anim { animation: vFade .3s ease both; }
  @keyframes vSpin { to { transform: rotate(360deg); } }
  #verify input:focus { outline: none; border-color: #E6017E; }
</style>

<div id="verify" x-data="{
        step: 'form', code: '', ref: '', error: '', result: [],
        get ready() { return this.code.trim().length > 0 && this.ref.trim().length > 0; },
        async submit() {
            if (!this.ready) { this.error = 'Enter both the verification code and the reference number. · أدخل رمز التحقق والرقم المرجعي.'; return; }
            this.step = 'checking'; this.error = '';
            try {
                const res = await fetch('{{ route('verify.check') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ code: this.code, ref: this.ref })
                });
                const j = await res.json();
                await new Promise(r => setTimeout(r, 700));
                if (j.valid) { this.result = this.build(j.data); this.step = 'valid'; }
                else { this.step = 'invalid'; }
            } catch (e) { this.step = 'invalid'; }
        },
        build(d) {
            return [
                { kEn: 'Reference', kAr: 'المرجع', v: d.ref, vAr: '' },
                { kEn: 'Report type', kAr: 'نوع التقرير', v: d.type, vAr: d.typeAr },
                { kEn: 'Patient', kAr: 'المريضة', v: d.patient, vAr: '' },
                { kEn: 'Issued', kAr: 'صدر في', v: d.issued, vAr: '' },
                { kEn: 'Clinic', kAr: 'العيادة', v: d.clinic, vAr: d.clinicAr },
                { kEn: 'Examining doctor', kAr: 'الطبيبة الفاحصة', v: d.doctor, vAr: '' },
            ].map(r => ({ ...r, hasAr: !!r.vAr }));
        },
        reset() { this.step = 'form'; this.code = ''; this.ref = ''; this.error = ''; },
        init() {
            // Scanning a report's QR code lands here with ?code=&ref= — verify automatically.
            const q = new URLSearchParams(window.location.search);
            const c = q.get('code'), r = q.get('ref');
            if (c && r) { this.code = c; this.ref = r; this.submit(); }
        }
    }"
    style="min-height: 100vh; display: flex; flex-direction: column; background: radial-gradient(120% 80% at 80% 0%, #FCE7F0 0%, #F4EEF1 45%, #F4EEF1 100%);">

  <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 40px;">
    <a href="{{ url('/') }}"><img src="{{ asset('assets/pink-caravan-wordmark.png') }}" alt="Pink Caravan" style="height: 54px; width: auto;" /></a>
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 12.5px; color: #9A8F97;">pinkcaravan.ae/verify</span>
      <div style="display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #EEDCE6; border-radius: 999px; padding: 7px 14px; font-size: 12.5px; font-weight: 600; color: #2E7D32;">🔒 Secure · آمن</div>
    </div>
  </div>

  <div style="flex: 1; display: flex; justify-content: center; padding: 24px 24px 70px;">
    <div style="width: 100%; max-width: 620px;">

      <div style="text-align: center; margin-bottom: 26px;">
        <div style="font-size: 11.5px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #E6017E;">Report verification · التحقق من التقرير</div>
        <h1 style="font-size: 32px; font-weight: 700; margin: 12px 0 8px; letter-spacing: -.02em;">Confirm a Pink Caravan report is authentic</h1>
        <p dir="rtl" style="font-size: 15px; color: #6B6472; margin: 0; line-height: 1.55;">تأكّد من صحة تقرير صادر عن القافلة الوردية بإدخال رمز التحقق المطبوع أسفل التقرير.</p>
      </div>

      {{-- FORM --}}
      <div x-show="step === 'form'" x-cloak class="v-anim">
        <div style="background: #fff; border: 1px solid #EFE2EA; border-radius: 20px; padding: 30px 32px; box-shadow: 0 10px 30px rgba(120,60,90,.08);">
          <label style="display: block; font-size: 12.5px; font-weight: 700; color: #6B4257; margin-bottom: 6px;">Verification code · رمز التحقق</label>
          <input x-model="code" @keydown.enter="submit()" placeholder="V-XXXX-XXXX" style="display: block; width: 100%; padding: 15px 16px; border: 1px solid #E3D2DC; border-radius: 12px; font-size: 20px; font-weight: 700; letter-spacing: .12em; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; text-transform: uppercase; color: #2A2230;" />
          <div style="font-size: 12px; color: #9A8F97; margin-top: 7px;">Printed at the bottom right of the report · مطبوع أسفل يمين التقرير</div>

          <label style="display: block; font-size: 12.5px; font-weight: 700; color: #6B4257; margin: 20px 0 6px;">Reference number · الرقم المرجعي</label>
          <input x-model="ref" @keydown.enter="submit()" placeholder="PC-2026-XXXXXX" style="display: block; width: 100%; padding: 13px 16px; border: 1px solid #E3D2DC; border-radius: 12px; font-size: 15px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #2A2230;" />

          <div x-show="error" x-cloak style="margin-top: 16px; background: #FBE4E4; border: 1px solid #F3C9C9; border-radius: 12px; padding: 12px 14px; font-size: 13px; color: #C62828; line-height: 1.5;">⚠ <span x-text="error"></span></div>

          <button type="button" @click="submit()" :style="ready ? { background: 'linear-gradient(90deg,#E6017E,#C0116E)' } : { background: '#DCC8D4' }" style="cursor: pointer; width: 100%; border: none; margin-top: 22px; color: #fff; font-weight: 700; font-size: 15px; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 6px 18px rgba(230,1,126,.2);">Verify report · تحقق من التقرير</button>

          <div style="display: flex; align-items: center; gap: 10px; margin: 20px 0 14px;"><div style="flex: 1; height: 1px; background: #F3E7EE;"></div><span style="font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #C7B3BF;">or · أو</span><div style="flex: 1; height: 1px; background: #F3E7EE;"></div></div>
          <div @click="$refs.codeInput?.focus()" role="button" style="cursor: pointer; text-align: center; border: 1px solid #E3D2DC; border-radius: 12px; padding: 12px; font-size: 13.5px; font-weight: 600; color: #6B4257;">📷 Scan the QR code on the report · امسح رمز QR</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px;">
          @foreach ([
            ['ic' => '🗂', 'tEn' => 'Registry-backed', 'tAr' => 'من السجل', 'd' => 'Checked against the campaign record store.'],
            ['ic' => '🛡', 'tEn' => 'No clinical data', 'tAr' => 'بدون بيانات سريرية', 'd' => 'Verification confirms issuance only.'],
            ['ic' => '⏱', 'tEn' => 'Valid 24 months', 'tAr' => 'صالح لمدة سنتين', 'd' => 'Codes expire after the screening cycle.'],
          ] as $a)
            <div style="background: #fff; border: 1px solid #EFE2EA; border-radius: 14px; padding: 14px 16px;">
              <div style="font-size: 18px;">{{ $a['ic'] }}</div>
              <div style="font-size: 12.5px; font-weight: 700; margin-top: 6px;"><span style="unicode-bidi: isolate; direction: ltr;">{{ $a['tEn'] }}</span> · <span style="unicode-bidi: isolate; direction: rtl;">{{ $a['tAr'] }}</span></div>
              <div style="font-size: 11.5px; color: #9A8F97; margin-top: 3px; line-height: 1.45;">{{ $a['d'] }}</div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- CHECKING --}}
      <div x-show="step === 'checking'" x-cloak class="v-anim" style="background: #fff; border: 1px solid #EFE2EA; border-radius: 20px; padding: 54px 32px; box-shadow: 0 10px 30px rgba(120,60,90,.08); text-align: center;">
        <div style="width: 42px; height: 42px; margin: 0 auto 18px; border-radius: 50%; border: 3px solid #F6D8E7; border-top-color: #E6017E; animation: vSpin .8s linear infinite;"></div>
        <div style="font-size: 15px; font-weight: 600;">Checking the campaign registry…</div>
        <div dir="rtl" style="font-size: 13px; color: #9A8F97; margin-top: 4px;">جارٍ التحقق من سجل الحملة…</div>
      </div>

      {{-- VALID --}}
      <div x-show="step === 'valid'" x-cloak class="v-anim" style="background: #fff; border: 1px solid #EFE2EA; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(120,60,90,.08);">
        <div style="background: #E4F4EF; padding: 26px 32px; border-bottom: 1px solid #D3EBE2; display: flex; align-items: center; gap: 16px;">
          <div style="width: 54px; height: 54px; flex-shrink: 0; border-radius: 50%; background: #2E7D32; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px;">✓</div>
          <div>
            <div style="font-size: 20px; font-weight: 700; color: #1F5C24;">Authentic Pink Caravan report</div>
            <div style="font-size: 14px; color: #3C7A46; margin-top: 3px;">تقرير أصلي صادر عن القافلة الوردية</div>
          </div>
        </div>
        <div style="padding: 22px 32px 26px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: #F3E7EE; border: 1px solid #F3E7EE; border-radius: 12px; overflow: hidden;">
            <template x-for="(r, i) in result" :key="i">
              <div style="background: #FDFAFC; padding: 12px 16px;">
                <div style="font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #B7A9B2;"><span style="unicode-bidi: isolate; direction: ltr;" x-text="r.kEn"></span> · <span style="unicode-bidi: isolate; direction: rtl;" x-text="r.kAr"></span></div>
                <div style="font-size: 14px; font-weight: 600; margin-top: 3px;"><span style="unicode-bidi: isolate; direction: ltr;" x-text="r.v"></span></div>
                <div x-show="r.hasAr" style="font-size: 12.5px; color: #6B6472; margin-top: 2px;"><span style="unicode-bidi: isolate; direction: rtl;" x-text="r.vAr"></span></div>
              </div>
            </template>
          </div>

          <div style="margin-top: 16px; background: #FAF4F7; border-radius: 12px; padding: 14px 16px; font-size: 12.5px; color: #6B6472; line-height: 1.6; display: flex; flex-direction: column; gap: 4px;">
            <span dir="ltr">Clinical findings are not shown here. Only the patient can open the full report with a one-time code.</span>
            <span dir="rtl">لا تُعرض النتائج السريرية هنا؛ يمكن للمريضة وحدها فتح التقرير الكامل برمز تحقق لمرة واحدة.</span>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 18px;">
            <a href="{{ route('patient') }}" style="text-decoration:none; flex: 1; text-align: center; background: linear-gradient(90deg,#E6017E,#C0116E); color: #fff; font-weight: 700; font-size: 13.5px; padding: 13px; border-radius: 12px; box-shadow: 0 5px 15px rgba(230,1,126,.2);">🔑 Patient sign-in · دخول المريضة</a>
            <div @click="reset()" role="button" style="cursor: pointer; flex: 1; text-align: center; border: 1px solid #E3D2DC; border-radius: 12px; padding: 13px; font-size: 13.5px; font-weight: 600; color: #6B4257;">Verify another · تحقق من تقرير آخر</div>
          </div>
        </div>
        <div style="background: #FAF4F7; border-top: 1px solid #F3E7EE; padding: 12px 32px; font-size: 11px; color: #9A8F97; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px 16px; line-height: 1.6;">
          <span style="white-space: nowrap;"><span style="unicode-bidi: isolate; direction: ltr;">Verified just now</span> · <span style="unicode-bidi: isolate; direction: rtl;">تم التحقق الآن</span></span>
          <span style="font-weight: 600; white-space: nowrap;">Friends of Cancer Patients (FOCP)</span>
        </div>
      </div>

      {{-- INVALID --}}
      <div x-show="step === 'invalid'" x-cloak class="v-anim" style="background: #fff; border: 1px solid #EFE2EA; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(120,60,90,.08);">
        <div style="background: #FBE4E4; padding: 26px 32px; border-bottom: 1px solid #F3C9C9; display: flex; align-items: center; gap: 16px;">
          <div style="width: 54px; height: 54px; flex-shrink: 0; border-radius: 50%; background: #C62828; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px;">!</div>
          <div>
            <div style="font-size: 20px; font-weight: 700; color: #8E1B1B;">No matching report found</div>
            <div style="font-size: 14px; color: #A33A3A; margin-top: 3px;">لم يتم العثور على تقرير مطابق</div>
          </div>
        </div>
        <div style="padding: 22px 32px 26px;">
          <div style="font-size: 13.5px; color: #453A44; line-height: 1.7;">
            Check the code and reference number, then try again. If they are printed exactly as entered, contact the campaign team on 800 424 — this document may not have been issued by Pink Caravan.
            <div dir="rtl" style="color: #6B6472; margin-top: 8px;">تحقق من الرمز والرقم المرجعي وأعد المحاولة. إذا كانا مطابقين لما هو مطبوع، تواصل مع فريق الحملة على 800 424.</div>
          </div>
          <div @click="reset()" role="button" style="cursor: pointer; margin-top: 20px; text-align: center; border: 1px solid #E3D2DC; border-radius: 12px; padding: 13px; font-size: 13.5px; font-weight: 700; color: #6B4257;">← Try again · إعادة المحاولة</div>
        </div>
      </div>

      <p style="text-align: center; font-size: 11.5px; color: #9A8F97; margin: 22px 0 0; line-height: 1.6;">
        <span dir="ltr" style="display: block;">Pink Caravan Breast Cancer Awareness Campaign — Friends of Cancer Patients (FOCP)</span>
        <span dir="rtl" style="display: block;">القافلة الوردية للتوعية بسرطان الثدي — أصدقاء مرضى السرطان</span>
      </p>
    </div>
  </div>
</div>
@endsection
