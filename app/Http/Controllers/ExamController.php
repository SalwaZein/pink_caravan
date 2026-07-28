<?php

namespace App\Http\Controllers;

use App\Models\ClinicalExamination;
use App\Models\PatientHistoryRecord;
use App\Support\Audit;
use App\Support\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Doctor — Clinical Breast Examination (Form 2).
 * The doctor may only open records assigned to them.
 */
class ExamController extends Controller
{
    /** Open the exam for an assigned record (moves it to "in review"). */
    public function edit(PatientHistoryRecord $record): RedirectResponse|View
    {
        $this->authorizeDoctor($record);

        if ($record->status === PatientHistoryRecord::ASSIGNED) {
            $record->update(['status' => PatientHistoryRecord::IN_REVIEW]);
        }

        $record->load('patient', 'clinic', 'examination');

        return view('staff.doctor.exam', [
            'record'      => $record,
            'exam'        => $record->examination ?? new ClinicalExamination(),
            'recoOptions' => __('pc.reco_options'),
            'sidebarRole' => 'doctor',
            'route'       => 'doctor/exam',
        ]);
    }

    public function update(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeDoctor($record);

        if ($record->status === PatientHistoryRecord::COMPLETED) {
            return redirect()->route('doctor.assigned')->with('status', __('pc.record_locked'));
        }

        $isSubmit = $request->input('action') === 'submit';

        $data = $request->validate([
            'symptoms'       => ['array'],
            'signs'          => ['array'],
            'pins'           => ['nullable', 'string'],
            'cbe_result'     => [$isSubmit ? 'required' : 'nullable', 'in:normal,abnormal'],
            'comments'       => ['nullable', 'string'],
            'other_findings' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'action'         => ['required', 'in:draft,submit'],
        ]);

        // The doctor's explicit Clinical Breast Examination result drives the record result.
        $result = $data['cbe_result'] ?? null;
        $pins = json_decode($data['pins'] ?? '[]', true) ?: [];

        DB::transaction(function () use ($record, $data, $isSubmit, $result, $pins) {
            $exam = $record->examination ?? new ClinicalExamination(['record_id' => $record->id]);
            $exam->fill([
                'doctor_id'      => auth()->id(),
                'exam_date'      => $exam->exam_date ?? now()->toDateString(),
                'venue_clinic_id'=> $record->clinic_id,
                'symptoms'       => $data['symptoms'] ?? [],
                'signs'          => $data['signs'] ?? [],
                'pins'           => $pins,
                'cbe_result'     => $data['cbe_result'] ?? $exam->cbe_result,
                'comments'       => $data['comments'] ?? null,
                'other_findings' => $data['other_findings'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
                'result'         => $isSubmit ? $result : ($data['cbe_result'] ?? $exam->result),
                'examiner_name'  => auth()->user()->name,
                'status'         => $isSubmit ? 'submitted' : 'draft',
                'attested_at'    => $isSubmit ? now() : $exam->attested_at,
            ]);
            $exam->save();

            if ($isSubmit) {
                $record->update(['status' => PatientHistoryRecord::COMPLETED]);
                ReportService::generate($record->fresh(['patient', 'examination', 'referrals']));
                Audit::log('exam.submitted', $record, "{$record->ref_no} — {$result}");
            } else {
                Audit::log('exam.saved', $record, $record->ref_no);
            }
        });

        return redirect()->route($isSubmit ? 'doctor.completed' : 'doctor.assigned')->with(
            'status',
            $isSubmit ? __('pc.exam_submitted') : __('pc.exam_saved'),
        );
    }

    /** Download the generated report PDF for a completed record. */
    public function report(PatientHistoryRecord $record): Response|RedirectResponse
    {
        $this->authorizeDoctor($record);

        abort_unless(in_array($record->status, [PatientHistoryRecord::COMPLETED, PatientHistoryRecord::REPORT_SENT], true), 404);

        $report = ReportService::ensure($record->loadMissing('patient', 'examination', 'referrals', 'clinic'));
        Audit::log('report.downloaded', $record, $record->ref_no);

        return ReportService::stream($record, $report);
    }

    /** Ensure the record belongs to the authenticated doctor. */
    private function authorizeDoctor(PatientHistoryRecord $record): void
    {
        abort_unless($record->assigned_doctor_id === auth()->id(), 403);
    }
}
