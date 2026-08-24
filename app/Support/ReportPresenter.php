<?php

namespace App\Support;

use App\Models\PatientHistoryRecord;
use App\Models\Report;

/**
 * Builds the bilingual (EN · AR) view-model for the CBE Report Document, ported
 * from the design's renderVals() logic. Fixed UI terms are shown bilingually
 * ("EN · AR"); free-text content (notes, findings, next steps) is shown in English.
 */
class ReportPresenter
{
    /** Static bilingual dictionary (mirrors the design's L.en / L.ar). */
    private const L = [
        'org'     => ['Friends of Cancer Patients — Pink Caravan', 'أصدقاء مرضى السرطان — القافلة الوردية'],
        'title'   => ['Clinical Breast Examination Report', 'تقرير الفحص السريري للثدي'],
        'ref'     => ['Reference', 'المرجع'],
        'issued'  => ['Issued', 'صدر في'],
        'status'  => ['Signed & released', 'موقّع ومعتمد'],
        'name'    => ['Name', 'الاسم'],
        'age'     => ['Age', 'العمر'],
        'regNo'   => ['Registration No.', 'رقم التسجيل'],
        'emirate' => ['Emirate', 'الإمارة'],
        'clinic'  => ['Clinic', 'العيادة'],
        'examDate'=> ['Examination date', 'تاريخ الفحص'],
        'result'  => ['Result', 'النتيجة'],
        'recommendation' => ['Recommendation', 'التوصية'],
        'nextSteps' => ['Next steps', 'الخطوات التالية'],
        'findings'  => ['Recorded symptoms & clinical signs', 'الأعراض والعلامات السريرية المسجّلة'],
        'findingCol'=> ['Finding', 'الملاحظة'],
        'typeCol'   => ['Type', 'النوع'],
        'sideCol'   => ['Side', 'الجهة'],
        'findingsEmpty' => ['No abnormal symptoms or signs recorded.', 'لا توجد أعراض أو علامات غير طبيعية مسجّلة.'],
        'map'       => ['Annotated findings map', 'خريطة مواضع الملاحظات'],
        'mapEmpty'  => ['No locations marked on the diagram.', 'لم تُحدَّد أي مواضع على المخطط.'],
        'notes'     => ['Doctor’s clinical notes', 'ملاحظات الطبيبة السريرية'],
        'careTeam'  => ['Care team', 'فريق الرعاية'],
        'docRole'   => ['Examined by (Doctor)', 'الفحص السريري (طبيبة)'],
        'nurseRole' => ['Registered by (Nurse)', 'التسجيل (ممرضة)'],
        'mammoRole' => ['Mammographer', 'فني الأشعة'],
        'attested'  => ['Electronically attested', 'معتمد إلكترونياً'],
        'verify'    => ['Verification code', 'رمز التحقق'],
        'verifyHint'=> ['Scan the QR code to verify this report, or enter the code at pinkcaravan.ae/verify', 'امسح رمز QR للتحقق من التقرير، أو أدخل الرمز على pinkcaravan.ae/verify'],
        'confidential' => [
            'This report is confidential and intended only for the named patient. A clinical breast examination is not a substitute for mammography.',
            'هذا التقرير سري ومخصص للمريضة المذكورة فقط. لا يُعدّ الفحص السريري للثدي بديلاً عن التصوير الشعاعي.',
        ],
        'symptom' => ['Symptom', 'عَرَض'],
        'sign'    => ['Sign', 'علامة'],
        'right'   => ['Right', 'يمين'],
        'left'    => ['Left', 'يسار'],
        'both'    => ['Right + Left', 'يمين + يسار'],
        'upper'   => ['Upper', 'علوي'],
        'lower'   => ['Lower', 'سفلي'],
        'outer'   => ['outer', 'خارجي'],
        'inner'   => ['inner', 'داخلي'],
        'quadrant'=> ['quadrant', 'ربع'],
        'normal'  => ['Normal', 'طبيعي'],
        // Patient-facing wording is deliberately reassuring: a clinical finding that needs
        // a routine follow-up scan is NOT a diagnosis, and alarming language causes panic.
        'abnormal'=> ['Further assessment recommended', 'يوصى بتقييم إضافي'],
        'normalNote'   => ['No concerning findings were recorded at this examination.', ''],
        'abnormalNote' => ['A routine follow-up imaging check (mammogram) is recommended to complete your screening. This is a common next step and does not confirm any diagnosis.', ''],
    ];

    private const NEXT_STEPS = [
        'normal' => [
            'Repeat clinical breast examination in 12 months.',
            'Monthly self-examination, a few days after your period ends.',
            'Contact a doctor immediately if any new change appears.',
        ],
        'abnormal' => [
            'Mammogram appointment within 7 days at a partnered facility.',
            'Care navigator will call to confirm the appointment and follow up results.',
            'Bring this report to the imaging appointment.',
        ],
    ];

    /** Patient next-steps checklist for a result — also used by the PDF renderer. */
    public static function nextSteps(bool $abnormal): array
    {
        return self::NEXT_STEPS[$abnormal ? 'abnormal' : 'normal'];
    }

    /** "EN · AR" bilingual join. */
    private static function bi(string $key): string
    {
        [$en, $ar] = self::L[$key];

        return $ar !== '' ? "{$en} · {$ar}" : $en;
    }

    private static function en(string $key): string
    {
        return self::L[$key][0];
    }

    public static function forRecord(PatientHistoryRecord $record, ?Report $report = null): array
    {
        $record->loadMissing('patient', 'examination', 'clinic', 'doctor', 'nurse', 'mammographer', 'referrals');
        $report ??= ReportService::ensure($record);

        $p  = $record->patient;
        $ex = $record->examination;
        $result = $ex?->cbe_result ?? $ex?->result ?? $record->finalResult();
        $abnormal = $result === 'abnormal';

        // Symptom / sign labels (EN + AR arrays from the lang files).
        $symEn = (array) __('pc.symptom_list', [], 'en');
        $symAr = (array) __('pc.symptom_list', [], 'ar');
        $sgnEn = (array) __('pc.sign_list', [], 'en');
        $sgnAr = (array) __('pc.sign_list', [], 'ar');

        $side = function (array $rl): string {
            $r = ! empty($rl['R']);
            $l = ! empty($rl['L']);
            return $r && $l ? self::en('both') : ($r ? self::en('right') : self::en('left'));
        };

        $findings = [];
        foreach (($ex?->symptoms ?? []) as $i => $rl) {
            if (empty($rl['R']) && empty($rl['L'])) continue;
            $label = $symEn[$i] ?? ($symAr[$i] ?? 'Finding');
            $findings[] = ['label' => $label, 'cat' => self::en('symptom'), 'catC' => '#7E4CC4', 'catBg' => '#EEE6FA', 'side' => $side($rl)];
        }
        foreach (($ex?->signs ?? []) as $i => $rl) {
            if (empty($rl['R']) && empty($rl['L'])) continue;
            $label = $sgnEn[$i] ?? ($sgnAr[$i] ?? 'Finding');
            $findings[] = ['label' => $label, 'cat' => self::en('sign'), 'catC' => '#2A6FDB', 'catBg' => '#E3ECFB', 'side' => $side($rl)];
        }

        // Breast-diagram pins → numbered markers + quadrant labels.
        $pins = [];
        foreach (($ex?->pins ?? []) as $n => $pin) {
            $x = (float) ($pin['x'] ?? 0);
            $y = (float) ($pin['y'] ?? 0);
            $sd = $x < 50 ? self::en('right') : self::en('left');
            $cx = $x < 50 ? 25 : 75;
            $vert = $y < 57 ? self::en('upper') : self::en('lower');
            $outward = $x < 50 ? $x < $cx : $x > $cx;
            $horiz = $outward ? self::en('outer') : self::en('inner');
            $pins[] = [
                'n'     => $n + 1,
                'left'  => $x.'%',
                'top'   => $y.'%',
                'label' => "{$sd} · {$vert} {$horiz} ".self::en('quadrant'),
            ];
        }

        $fmt = fn ($d) => $d ? $d->format('d M Y · H:i') : '—';
        $emirateEn = $p?->emirate ? __('pc.em_'.$p->emirate, [], 'en') : '—';

        $team = array_values(array_filter([
            ['role' => self::bi('docRole'),   'name' => $record->doctor?->name,       'at' => $fmt($ex?->attested_at ?? $record->submitted_at)],
            ['role' => self::bi('nurseRole'), 'name' => $record->nurse?->name,        'at' => $fmt($record->submitted_at)],
            $record->mammographer ? ['role' => self::bi('mammoRole'), 'name' => $record->mammographer->name, 'at' => $fmt($record->report_uploaded_at)] : null,
        ]));

        return [
            'dir'   => 'ltr',
            'f'     => [
                'org' => self::bi('org'), 'title' => self::en('title'), 'titleAlt' => self::L['title'][1],
                'ref' => self::bi('ref'), 'issued' => self::en('issued'), 'status' => self::bi('status'),
                'result' => self::bi('result'), 'recommendation' => self::bi('recommendation'), 'nextSteps' => self::bi('nextSteps'),
                'findings' => self::bi('findings'), 'findingCol' => self::bi('findingCol'), 'typeCol' => self::bi('typeCol'), 'sideCol' => self::bi('sideCol'),
                'findingsEmpty' => self::en('findingsEmpty'),
                'map' => self::bi('map'), 'mapEmpty' => self::en('mapEmpty'),
                'notes' => self::bi('notes'), 'careTeam' => self::bi('careTeam'),
                'attested' => self::en('attested'), 'verify' => self::bi('verify'), 'verifyHint' => self::en('verifyHint'),
                'confidential' => self::en('confidential'),
            ],
            'meta' => [
                ['k' => self::bi('name'),     'v' => $p?->full_name ?? '—', 'dir' => 'ltr'],
                ['k' => self::bi('age'),      'v' => (string) ($p?->dob?->age ?? '—'), 'dir' => 'ltr'],
                ['k' => self::bi('regNo'),    'v' => $record->ref_no, 'dir' => 'ltr'],
                ['k' => self::bi('emirate'),  'v' => $emirateEn, 'dir' => 'ltr'],
                ['k' => self::bi('clinic'),   'v' => $record->clinic?->name ?? '—', 'dir' => 'ltr'],
                ['k' => self::bi('examDate'), 'v' => $ex?->exam_date?->format('d M Y') ?? $fmt($record->submitted_at), 'dir' => 'ltr'],
            ],
            'findings'    => $findings,
            'hasFindings' => count($findings) > 0,
            'pins'        => $pins,
            'hasPins'     => count($pins) > 0,
            'team'        => $team,
            'resultText'  => self::bi($abnormal ? 'abnormal' : 'normal'),
            // Amber (not alarming red) for the follow-up case, green for normal.
            'resultColor' => $abnormal ? '#B25E00' : '#2E7D32',
            'resultBg'    => $abnormal ? '#FBEEDD' : '#E4F4EF',
            'resultNote'  => self::en($abnormal ? 'abnormalNote' : 'normalNote'),
            'recText'     => $ex?->recommendation ?: self::en($abnormal ? 'abnormal' : 'normal'),
            'notesText'   => $ex?->comments ?: '—',
            'nextSteps'   => self::nextSteps($abnormal),
            'data'        => [
                'refNo'      => $record->ref_no,
                'issuedAt'   => optional($report->generated_at ?? $record->submitted_at)->format('d M Y'),
                'attestedAt' => $fmt($ex?->attested_at),
                'verifyCode' => $report->verify_code,
                'verifyUrl'  => QrCode::verifyUrl($report->verify_code, $record->ref_no),
                'qr'         => QrCode::svg(QrCode::verifyUrl($report->verify_code, $record->ref_no)),
            ],
        ];
    }
}
