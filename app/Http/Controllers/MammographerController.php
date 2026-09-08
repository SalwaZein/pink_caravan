<?php

namespace App\Http\Controllers;

use App\Models\MammogramFinding;
use App\Models\PatientHistoryRecord;
use App\Support\Audit;
use App\Support\PatientNotifier;
use App\Support\RecordPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Mammographer workspace — the patient's file from the mammography side.
 *
 * Business feedback #4–#6:
 *   #4 the mammographer opens the patient's file and reviews the initial
 *      registration form (Form 3) before assessing;
 *   #5 records the mammography findings on a dedicated form in that file;
 *   #6 the formal report may only be ready days later, so a case she has
 *      handled stays open to her — visible in a second "awaiting report" list —
 *      until the report is uploaded and sent, whatever the admin has done with
 *      the case in the meantime.
 */
class MammographerController extends Controller
{
    /** Cases assigned to this mammographer, plus the ones still awaiting a report. */
    public function queue(): View
    {
        $mine = $this->scopedQuery()->where('mammographer_id', auth()->id());

        $assigned = (clone $mine)
            ->with('patient', 'doctor', 'examination')
            ->where('assigned_role', PatientHistoryRecord::ROLE_MAMMOGRAPHER)
            ->whereIn('status', [PatientHistoryRecord::ASSIGNED, PatientHistoryRecord::IN_REVIEW])
            ->latest()->get();

        // #6 — handled but the report has not gone out yet. These stay reachable
        // however the case has moved on (returned to the admin, or even closed).
        $pending = (clone $mine)
            ->with('patient', 'doctor', 'examination')
            ->whereNull('report_sent_at')
            ->whereNotIn('id', $assigned->pluck('id'))
            ->latest()->get();

        return view('staff.mammographer.queue', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'mammographer/queue',
            'rows'        => RecordPresenter::rows($assigned, 'mammographer.record'),
            'pendingRows' => RecordPresenter::rows($pending, 'mammographer.record'),
        ]);
    }

    /** Open the patient's file (report handling + links to the history and findings). */
    public function edit(PatientHistoryRecord $record): View
    {
        $this->authorizeAssigned($record);

        // Opening a freshly assigned case marks it as being worked on.
        if ($record->status === PatientHistoryRecord::ASSIGNED) {
            $record->update(['status' => PatientHistoryRecord::IN_REVIEW]);
        }

        $record->load('patient', 'clinic', 'doctor', 'nurse', 'examination', 'mammogramFinding');

        return view('staff.mammographer.manage', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'mammographer/queue',
            'record'      => $record,
            'patient'     => $record->patient,
            'finding'     => $record->mammogramFinding,
            'canEdit'     => $this->canStillWork($record),
        ]);
    }

    /**
     * #4 — the initial patient form (Form 3) as submitted at registration,
     * rendered read-only inside the mammographer's copy of the file.
     */
    public function history(PatientHistoryRecord $record): View
    {
        $this->authorizeAssigned($record);

        $record->load('patient', 'referrals');

        return view('staff.nurse.record', [
            'record'       => $record,
            'patient'      => $record->patient,
            'sidebarRole'  => auth()->user()->sidebarRole(),
            'route'        => 'mammographer/queue',
            'formAction'   => route('mammographer.record', $record),
            'backUrl'      => route('mammographer.record', $record),
            'readOnly'     => true,
            'readOnlyNote' => __('pc.patient_file_readonly'),
            'canAssign'    => false,
            'assignees'    => ['nurse' => collect(), 'doctor' => collect(), 'mammographer' => collect()],
        ]);
    }

    /** #5 — the mammography findings form for this case. */
    public function findings(PatientHistoryRecord $record): View
    {
        $this->authorizeAssigned($record);

        $record->load('patient', 'clinic', 'mammogramFinding');

        return view('staff.mammographer.findings', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'mammographer/queue',
            'record'      => $record,
            'patient'     => $record->patient,
            'finding'     => $record->mammogramFinding ?? new MammogramFinding(['status' => MammogramFinding::DRAFT]),
            'readOnly'    => ! $this->canStillWork($record),
        ]);
    }

    /** Save the findings as a draft, or submit them into the patient's file. */
    public function saveFindings(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeAssigned($record);

        if (! $this->canStillWork($record)) {
            return redirect()->route('mammographer.record', $record)->with('status', __('pc.findings_locked'));
        }

        $isSubmit = $request->input('action') === 'submit';

        $rules = [
            'exam_date'      => ['nullable', 'date'],
            'modality'       => ['nullable', 'in:'.implode(',', MammogramFinding::MODALITIES)],
            'breast_density' => ['nullable', 'in:'.implode(',', MammogramFinding::DENSITIES)],
            'birads_right'   => ['nullable', 'in:'.implode(',', MammogramFinding::BIRADS)],
            'birads_left'    => ['nullable', 'in:'.implode(',', MammogramFinding::BIRADS)],
            'findings_right' => ['nullable', 'string', 'max:4000'],
            'findings_left'  => ['nullable', 'string', 'max:4000'],
            'comparison'     => ['nullable', 'string', 'max:2000'],
            'impression'     => ['nullable', 'string', 'max:4000'],
            'recommendation' => ['nullable', 'in:'.implode(',', MammogramFinding::RECOMMENDATIONS)],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ];

        // Submitting is what makes the findings part of the record, so the
        // clinically meaningful fields become mandatory at that point only.
        if ($isSubmit) {
            $rules['exam_date']      = ['required', 'date'];
            $rules['modality']       = ['required', 'in:'.implode(',', MammogramFinding::MODALITIES)];
            $rules['birads_right']   = ['required', 'in:'.implode(',', MammogramFinding::BIRADS)];
            $rules['birads_left']    = ['required', 'in:'.implode(',', MammogramFinding::BIRADS)];
            $rules['impression']     = ['required', 'string', 'max:4000'];
            $rules['recommendation'] = ['required', 'in:'.implode(',', MammogramFinding::RECOMMENDATIONS)];
        }

        $data = $request->validate($rules);

        $finding = $record->mammogramFinding ?? new MammogramFinding(['record_id' => $record->id]);
        $finding->fill($data);
        $finding->record_id       = $record->id;
        $finding->mammographer_id = $finding->mammographer_id ?? auth()->id();
        $finding->status          = $isSubmit ? MammogramFinding::SUBMITTED : MammogramFinding::DRAFT;
        $finding->submitted_at    = $isSubmit ? ($finding->submitted_at ?? now()) : null;
        $finding->save();

        // Whoever files the findings owns the case on the mammography side.
        if (! $record->mammographer_id) {
            $record->update(['mammographer_id' => auth()->id()]);
        }

        Audit::log($isSubmit ? 'mammogram.findings_submitted' : 'mammogram.findings_drafted', $record, $record->ref_no);

        return redirect()->route($isSubmit ? 'mammographer.record' : 'mammographer.findings', $record)
            ->with('status', $isSubmit ? __('pc.findings_submitted_ok') : __('pc.findings_saved_ok'));
    }

    /** Save patient/PC details and, optionally, upload the mammogram report file. */
    public function update(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeAssigned($record);

        if (! $this->canStillWork($record)) {
            return redirect()->route('mammographer.record', $record)->with('status', __('pc.report_already_sent'));
        }

        $data = $request->validate([
            'manual_pc_number' => ['nullable', 'string', 'max:60'],
            'full_name'        => ['required', 'string', 'max:255'],
            'mobile1'          => ['required', 'string', 'max:40'],
            'mobile2'          => ['nullable', 'string', 'max:40'],
            'email'            => ['nullable', 'email', 'max:255'],
            'report'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $record->patient->update([
            'manual_pc_number' => $data['manual_pc_number'] ?? null,
            'full_name'        => $data['full_name'],
            'mobile1'          => $data['mobile1'],
            'mobile2'          => $data['mobile2'] ?? null,
            'email'            => $data['email'] ?? null,
        ]);

        $record->mammographer_id = $record->mammographer_id ?? auth()->id();

        $uploaded = false;
        if ($request->hasFile('report')) {
            if ($record->mammogram_report_path && Storage::exists($record->mammogram_report_path)) {
                Storage::delete($record->mammogram_report_path);
            }
            $record->mammogram_report_path = $request->file('report')
                ->storeAs('mammograms', $record->ref_no.'-'.now()->format('YmdHis').'.pdf');
            $record->report_uploaded_at = now();
            $uploaded = true;
        }

        $record->save();

        Audit::log($uploaded ? 'report.uploaded' : 'record.updated', $record, $record->ref_no);

        return redirect()->route('mammographer.record', $record)->with(
            'status',
            $uploaded ? __('pc.report_uploaded_ok') : __('pc.pc_saved'),
        );
    }

    /** Send the uploaded mammogram report to the patient. */
    public function send(PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeAssigned($record);

        if (! $record->mammogram_report_path) {
            return back()->with('status', __('pc.report_needed_first'));
        }

        $record->report_sent_at = now();

        // An open case returns to the clinic admin to be closed or routed on.
        // A case the admin already closed (#6: the report arrived days later)
        // stays closed — sending the report must not reopen it.
        if (! $record->isClosed()) {
            $record->status = PatientHistoryRecord::RETURNED;
        }

        $record->save();

        // Notify the patient across email + SMS + WhatsApp with a secure portal link.
        $channels = PatientNotifier::reportReady($record);
        if ($report = $record->report) {
            $report->update(['delivery' => array_merge($report->delivery ?? [], $channels, ['portal' => 'ready'])]);
        }

        Audit::log('report.sent', $record, "{$record->ref_no} — ".self::channelSummary($channels));

        return redirect()->route('mammographer.queue')
            ->with('status', __('pc.report_sent_ok').' ('.self::channelSummary($channels).')');
    }

    /** Stream the uploaded mammogram report PDF (staff view). */
    public function viewReport(PatientHistoryRecord $record)
    {
        $this->authorizeScope($record);

        abort_unless($record->mammogram_report_path && Storage::exists($record->mammogram_report_path), 404);

        return response(Storage::get($record->mammogram_report_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="mammogram-'.$record->ref_no.'.pdf"',
        ]);
    }

    // ---- helpers ----

    /**
     * #6 — the file stays writable until the report has actually been sent,
     * even after the clinic admin has closed the case. Once sent, it is history.
     */
    private function canStillWork(PatientHistoryRecord $record): bool
    {
        return $record->report_sent_at === null;
    }

    /** Human-readable per-channel notification summary, e.g. "Email: sent, SMS: logged". */
    private static function channelSummary(array $channels): string
    {
        $labels = ['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'];

        return collect($channels)->map(fn ($status, $ch) => ($labels[$ch] ?? $ch).': '.$status)->join(', ');
    }

    private function scopedQuery(): Builder
    {
        $clinicIds = auth()->user()->clinicIds();
        $q = PatientHistoryRecord::query();

        return empty($clinicIds) ? $q : $q->whereIn('clinic_id', $clinicIds);
    }

    private function authorizeScope(PatientHistoryRecord $record): void
    {
        $clinicIds = auth()->user()->clinicIds();
        // A permitted user with no clinic assignment (e.g. super admin) sees everything.
        abort_unless(empty($clinicIds) || in_array($record->clinic_id, $clinicIds, true), 403);
    }

    /**
     * The case must be in this user's clinic AND belong to them on the
     * mammography side — either currently routed to them, or previously handled
     * by them (which is what keeps a case open for a late report, #6).
     * A clinic-less super admin (permission only, no clinic) keeps full access.
     */
    private function authorizeAssigned(PatientHistoryRecord $record): void
    {
        $this->authorizeScope($record);

        if (empty(auth()->user()->clinicIds())) {
            return;
        }

        $routedToMe = $record->assigned_role === PatientHistoryRecord::ROLE_MAMMOGRAPHER
            && $record->mammographer_id === auth()->id();

        abort_unless($routedToMe || $record->mammographer_id === auth()->id(), 403);
    }
}
