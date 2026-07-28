<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; color: #2A2230; font-size: 12px; margin: 0; padding: 0; }
    .wrap { padding: 28px 34px; }
    .head { border-bottom: 3px solid #E6017E; padding-bottom: 14px; margin-bottom: 18px; }
    .brand { font-size: 22px; font-weight: bold; color: #E6017E; }
    .brand small { display: block; font-size: 11px; color: #6B6472; font-weight: normal; margin-top: 2px; }
    h1 { font-size: 16px; margin: 18px 0 4px; }
    .muted { color: #9A8F97; }
    table { width: 100%; border-collapse: collapse; }
    .info td { padding: 6px 8px; border: 1px solid #EFE2EA; font-size: 12px; }
    .info td.k { background: #FAF4F7; color: #6B4257; font-weight: bold; width: 26%; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 10px; font-weight: bold; font-size: 12px; }
    .normal { background: #E4F4EF; color: #2E7D32; }
    .abnormal { background: #FBE4E4; color: #C62828; }
    .section { margin-top: 18px; }
    .section h2 { font-size: 13px; color: #E6017E; border-bottom: 1px solid #EFE2EA; padding-bottom: 5px; margin: 0 0 8px; }
    .foot { margin-top: 28px; border-top: 1px solid #EFE2EA; padding-top: 12px; font-size: 10px; color: #9A8F97; }
</style>
</head>
<body>
@php
    $p = $record->patient;
    $ex = $record->examination;
    $result = $record->finalResult();
@endphp
<div class="wrap">
    <div class="head">
        <div class="brand">Pink Caravan · القافلة الوردية
            <small>Friends of Cancer Patients (FOCP) — Riding for Courage · أصدقاء مرضى السرطان</small>
        </div>
    </div>

    <h1>Clinical Breast Examination Report · تقرير الفحص السريري للثدي</h1>
    <div class="muted" style="margin-bottom:14px;">Reference: <strong>{{ $record->ref_no }}</strong> · Generated {{ now()->format('d M Y') }}</div>

    <table class="info">
        <tr><td class="k">Name · الاسم</td><td>{{ $p->full_name }}</td><td class="k">Age · العمر</td><td>{{ $p->dob?->age ?? '—' }}</td></tr>
        <tr><td class="k">Registration No. · رقم التسجيل</td><td>{{ $p->pc_number }}</td><td class="k">PC Number</td><td>{{ $p->manual_pc_number ?? '—' }}</td></tr>
        <tr><td class="k">Emirate · الإمارة</td><td>{{ $p->emirate ? __('pc.em_'.$p->emirate) : '—' }}</td><td class="k">Examination Date · تاريخ الفحص</td><td>{{ optional($ex?->exam_date ?? $record->submitted_at)->format('d M Y') ?? '—' }}</td></tr>
        <tr><td class="k">Clinic · العيادة</td><td colspan="3">{{ $record->clinic?->name ?? '—' }}</td></tr>
    </table>

    <div class="section">
        <h2>Result · النتيجة</h2>
        <span class="badge {{ $result === 'abnormal' ? 'abnormal' : 'normal' }}">
            {{ $result === 'abnormal' ? 'Abnormal · غير طبيعي' : 'Normal · طبيعي' }}
        </span>
    </div>

    @if ($ex)
        <div class="section">
            <h2>Recommendation · التوصية</h2>
            <div>{{ $ex->recommendation ?? '—' }}</div>
        </div>
        @if ($ex->comments)
            <div class="section">
                <h2>Comments · ملاحظات</h2>
                <div>{{ $ex->comments }}</div>
            </div>
        @endif
    @endif

    @if ($record->referrals->isNotEmpty())
        <div class="section">
            <h2>Referrals · الإحالات</h2>
            <table class="info">
                <tr><td class="k">Type</td><td class="k">Hospital</td><td class="k">Date</td><td class="k">Status</td></tr>
                @foreach ($record->referrals as $ref)
                    <tr>
                        <td>{{ strtoupper($ref->type) }}</td>
                        <td>{{ $ref->hospital ?? '—' }}</td>
                        <td>{{ optional($ref->referral_date)->format('d M Y') ?? '—' }}</td>
                        <td>{{ ucfirst($ref->status) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="section">
        <h2>Care team · فريق الرعاية</h2>
        <table class="info">
            <tr><td class="k">Examined by (Doctor)</td><td>{{ $ex?->examiner_name ?? $record->doctor?->name ?? '—' }}</td></tr>
            <tr><td class="k">Registered by (Nurse)</td><td>{{ $record->nurse?->name ?? '—' }}</td></tr>
            <tr><td class="k">Mammographer</td><td>{{ $record->mammographer?->name ?? '—' }}</td></tr>
        </table>
        @if ($ex?->attested_at)<div class="muted" style="margin-top:6px;">Attested {{ $ex->attested_at->format('d M Y') }}</div>@endif
    </div>

    @isset($verifyCode)
        <div class="section">
            <h2>Verification · التحقق</h2>
            <table class="info">
                <tr>
                    <td class="k">Verification code · رمز التحقق</td>
                    <td><strong style="letter-spacing:1px;">{{ $verifyCode }}</strong></td>
                    <td class="k">Validate at · تحقّق على</td>
                    <td>pinkcaravan.ae/verify</td>
                </tr>
            </table>
        </div>
    @endisset

    <div class="foot">
        This report is confidential and intended only for the named patient. · هذا التقرير سري ومخصص للمريضة المذكورة فقط.<br>
        Pink Caravan Breast Cancer Awareness Campaign — Friends of Cancer Patients (FOCP).
    </div>
</div>
</body>
</html>
