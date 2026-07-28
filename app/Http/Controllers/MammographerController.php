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
 * Mammographer — post-campaign report handling.
 * Enters the manual PC number, uploads the mammogram report and sends it to the
 * patient. Scoped to the mammographer's assigned clinics.
 */
class MammographerController extends Controller
{
    /** Records that have completed the clinical exam and are ready for the report. */
    public function queue(): View
    {
        $records = $this->scopedQuery()
            ->with('patient', 'doctor', 'examination')
            ->whereIn('status', [PatientHistoryRecord::COMPLETED, PatientHistoryRecord::REPORT_SENT])
            ->latest()->get();

        return view('staff.mammographer.queue', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'mammographer/queue',
            'rows'        => RecordPresenter::rows($records, 'mammographer.record'),
        ]);
    }

    /** Open the manage-report page for a record (claims it as this mammographer). */
    public function edit(PatientHistoryRecord $record): View
    {
        $this->authorizeScope($record);

        if (! $record->mammographer_id) {
            $record->update(['mammographer_id' => auth()->id()]);
        }

        $record->load('patient', 'clinic', 'doctor', 'nurse', 'examination');

        return view('staff.mammographer.manage', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'mammographer/queue',
            'record'      => $record,
            'patient'     => $record->patient,
        ]);
    }

    /** Save patient/PC details and, optionally, upload the mammogram report file. */
    public function update(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeScope($record);

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

    /** Send the uploaded mammogram report to the patient (delivery stubbed like OTP/SMS). */
    public function send(PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeScope($record);

        if (! $record->mammogram_report_path) {
            return back()->with('status', __('pc.report_needed_first'));
        }

        $record->update([
            'mammographer_id' => $record->mammographer_id ?? auth()->id(),
            'report_sent_at'  => now(),
            'status'          => PatientHistoryRecord::REPORT_SENT,
        ]);

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

    /** Human-readable per-channel notification summary, e.g. "Email: sent, SMS: logged, WhatsApp: logged". */
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
}
