<?php

namespace App\Http\Controllers;

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
 * Radiologist workspace — one tab, "Patient Report".
 *
 * The mammographer submits the Mammography Screening and assigns it here. The
 * radiologist sees the patient's identity, the PC number the mammographer entered
 * and the findings she recorded, attaches the final PDF report produced by the
 * radiology medical system, and sends that PDF straight to the patient.
 */
class RadiologistController extends Controller
{
    /** The "Patient Report" tab: studies waiting on this radiologist, then the ones reported. */
    public function index(): View
    {
        $mine = $this->scopedQuery()->where('radiologist_id', auth()->id());

        $open = (clone $mine)
            ->with('patient', 'mammogramFinding')
            ->whereNull('radiology_report_sent_at')
            ->latest()->get();

        $sent = (clone $mine)
            ->with('patient', 'mammogramFinding')
            ->whereNotNull('radiology_report_sent_at')
            ->latest('radiology_report_sent_at')->get();

        return view('staff.radiologist.reports', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'radiologist/reports',
            'rows'        => RecordPresenter::rows($open, 'radiologist.report'),
            'sentRows'    => RecordPresenter::rows($sent, 'radiologist.report'),
        ]);
    }

    /** One patient's report: what the mammographer filed, plus the final PDF. */
    public function show(PatientHistoryRecord $record): View
    {
        $this->authorizeAssigned($record);

        $record->load('patient', 'clinic', 'mammographer', 'mammogramFinding');

        return view('staff.radiologist.report', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'radiologist/reports',
            'record'      => $record,
            'patient'     => $record->patient,
            'finding'     => $record->mammogramFinding,
        ]);
    }

    /** Attach (or replace) the final PDF report from the radiology system. */
    public function upload(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeAssigned($record);

        $request->validate([
            'report' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($record->radiology_report_path && Storage::exists($record->radiology_report_path)) {
            Storage::delete($record->radiology_report_path);
        }

        $record->radiology_report_path = $request->file('report')
            ->storeAs('radiology-reports', $record->ref_no.'-'.now()->format('YmdHis').'.pdf');
        $record->radiology_report_uploaded_at = now();
        $record->save();

        Audit::log('radiology.report_uploaded', $record, $record->ref_no);

        return redirect()->route('radiologist.report', $record)->with('status', __('pc.rad_uploaded_ok'));
    }

    /** Send the final PDF report to the patient (email attachment + SMS/WhatsApp notice). */
    public function send(PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeAssigned($record);

        if (! $record->radiology_report_path) {
            return back()->with('status', __('pc.rad_upload_first'));
        }

        $channels = PatientNotifier::radiologyReport($record);

        $record->radiology_report_sent_at = now();

        // The case goes back to the clinic admin to be closed or routed on — unless
        // the admin already closed it, in which case sending must not reopen it.
        if (! $record->isClosed()) {
            $record->status = PatientHistoryRecord::RETURNED;
        }

        $record->save();

        Audit::log('radiology.report_sent', $record, "{$record->ref_no} — ".self::channelSummary($channels));

        return redirect()->route('radiologist.reports')
            ->with('status', __('pc.rad_sent_ok').' ('.self::channelSummary($channels).')');
    }

    /** Stream the attached PDF report (staff view). */
    public function report(PatientHistoryRecord $record)
    {
        $this->authorizeAssigned($record);

        abort_unless($record->radiology_report_path && Storage::exists($record->radiology_report_path), 404);

        return response(Storage::get($record->radiology_report_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="radiology-'.$record->ref_no.'.pdf"',
        ]);
    }

    // ---- helpers ----

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

    /**
     * The study must be in this user's clinic and assigned to them. A clinic-less
     * holder of the permission (a super admin) keeps full access.
     */
    private function authorizeAssigned(PatientHistoryRecord $record): void
    {
        $clinicIds = auth()->user()->clinicIds();
        abort_unless(empty($clinicIds) || in_array($record->clinic_id, $clinicIds, true), 403);

        if (empty($clinicIds)) {
            return;
        }

        abort_unless($record->radiologist_id === auth()->id(), 403);
    }
}
