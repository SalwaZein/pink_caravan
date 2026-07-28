<?php

namespace App\Http\Controllers;

use App\Models\PatientHistoryRecord;
use App\Models\Report;
use App\Support\ReportPresenter;
use App\Support\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** Staff view of the bilingual CBE Report Document (print → PDF). */
    public function document(PatientHistoryRecord $record): View
    {
        $this->authorizeStaff($record);
        abort_unless(in_array($record->status, [PatientHistoryRecord::COMPLETED, PatientHistoryRecord::REPORT_SENT], true), 404);

        $report = ReportService::ensure($record->loadMissing('patient', 'examination', 'referrals', 'clinic', 'doctor', 'nurse', 'mammographer'));

        return view('reports.document', [
            'vm'   => ReportPresenter::forRecord($record, $report),
            'back' => url()->previous(),
        ]);
    }

    /** Public verification landing page. */
    public function verifyForm(): View
    {
        return view('reports.verify');
    }

    /** Public verification check — confirms issuance only, no clinical data. */
    public function verifyCheck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'ref'  => ['required', 'string', 'max:30'],
        ]);

        $code = strtoupper(trim($data['code']));
        $ref  = strtoupper(trim($data['ref']));

        $report = Report::where('verify_code', $code)
            ->with(['record.patient', 'record.clinic', 'record.doctor'])
            ->first();

        $rec = $report?->record;
        $valid = $report && $rec
            && strtoupper((string) $rec->ref_no) === $ref
            && in_array($rec->status, [PatientHistoryRecord::COMPLETED, PatientHistoryRecord::REPORT_SENT], true);

        if (! $valid) {
            return response()->json(['valid' => false]);
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'ref'      => $rec->ref_no,
                'type'     => 'Clinical Breast Examination',
                'typeAr'   => 'الفحص السريري للثدي',
                'patient'  => self::maskName($rec->patient?->full_name ?? ''),
                'issued'   => optional($report->generated_at)->format('d M Y') ?? '—',
                'clinic'   => $rec->clinic?->name ?? '—',
                'clinicAr' => '',
                'doctor'   => $rec->doctor?->name ?? '—',
            ],
        ]);
    }

    /** Mask a patient name for public display: "Aisha M." → "A***a M." */
    public static function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (empty($parts[0])) {
            return '—';
        }

        $first = $parts[0];
        $masked = mb_strlen($first) <= 2
            ? mb_substr($first, 0, 1).'*'
            : mb_substr($first, 0, 1).'***'.mb_substr($first, -1);

        $last = count($parts) > 1 ? ' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : '';

        return $masked.$last;
    }

    /** Staff may view a report if they examined it, manage its clinic, or can see dashboards. */
    private function authorizeStaff(PatientHistoryRecord $record): void
    {
        $u = auth()->user();
        $inClinic = in_array($record->clinic_id, $u->clinicIds(), true);

        abort_unless(
            $record->assigned_doctor_id === $u->id
            || $u->can('view_dashboards')
            || ($u->can('manage_mammograms') && $inClinic),
            403,
        );
    }
}
