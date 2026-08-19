<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\Patient;
use App\Models\PatientHistoryRecord;
use App\Support\Audit;
use App\Support\ReportPresenter;
use App\Support\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Patient self-service — OTP access (no password) to view/download their report.
 * OTP delivery is stubbed (logged) until an SMS/email gateway is configured.
 */
class PatientController extends Controller
{
    public function access(): View
    {
        return view('public.patient', ['step' => 1]);
    }

    /** Step 1 → generate + "send" an OTP for the given mobile. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'max:40']]);
        $mobile = $this->normalise($data['mobile']);

        // Only issue a code if a patient exists with a completed report (avoids user enumeration in UI copy).
        $patient = $this->findPatient($mobile);
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        if ($patient) {
            OtpCode::where('mobile', $mobile)->whereNull('consumed_at')->delete();
            OtpCode::create(['mobile' => $mobile, 'code' => $code, 'expires_at' => now()->addMinutes(10)]);
            // Stub delivery: log the code. Replace with SMS/email gateway when available.
            Log::info("Pink Caravan OTP for {$mobile}: {$code}");
        }

        $request->session()->put('otp_mobile', $mobile);

        return redirect()->route('patient.otp')
            ->with('dev_code', config('app.debug') && $patient ? $code : null);
    }

    public function otpForm(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('otp_mobile')) {
            return redirect()->route('patient');
        }

        return view('public.patient', [
            'step'     => 2,
            'mobile'   => $request->session()->get('otp_mobile'),
            'devCode'  => session('dev_code'),
        ]);
    }

    /** Step 2 → verify the code and open the report session. */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:4']]);
        $mobile = $request->session()->get('otp_mobile');

        $otp = OtpCode::where('mobile', $mobile)->whereNull('consumed_at')->latest()->first();

        if (! $otp || ! $otp->isValid() || $otp->code !== $data['code']) {
            $otp?->increment('attempts');
            return back()->withErrors(['code' => __('pc.otp_invalid')]);
        }

        $otp->update(['consumed_at' => now()]);
        $patient = $this->findPatient($mobile);
        $request->session()->put('patient_id', $patient->id);

        Audit::log('patient.otp_verified', $patient, $patient->pc_number);

        return redirect()->route('patient.report');
    }

    public function report(Request $request): RedirectResponse|View
    {
        $record = $this->authedRecord($request);
        if (! $record) {
            return redirect()->route('patient');
        }

        return view('public.patient', [
            'step'   => 3,
            'record' => $record->load('patient', 'examination'),
        ]);
    }

    /** Full bilingual report document (with Print / PDF) for the authenticated patient. */
    public function document(Request $request): RedirectResponse|View
    {
        $record = $this->authedRecord($request);
        if (! $record) {
            return redirect()->route('patient');
        }

        $report = ReportService::ensure($record->loadMissing('patient', 'examination', 'referrals', 'clinic', 'doctor', 'nurse', 'mammographer'));

        return view('reports.document', [
            'vm'       => ReportPresenter::forRecord($record, $report),
            'back'     => route('patient.report'),
            'download' => route('patient.report.download'),
        ]);
    }

    /** Stream the PDF report. */
    public function download(Request $request): Response|RedirectResponse
    {
        $record = $this->authedRecord($request);
        if (! $record) {
            return redirect()->route('patient');
        }

        $report = ReportService::ensure($record);
        Audit::log('report.downloaded', $record, $record->ref_no);

        return ReportService::stream($record, $report);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['otp_mobile', 'patient_id']);
        return redirect()->route('patient');
    }

    // ---- helpers ----

    private function findPatient(string $mobile): ?Patient
    {
        return Patient::where('mobile1', $mobile)->orWhere('mobile2', $mobile)
            ->whereHas('record.report')
            ->latest()->first();
    }

    private function authedRecord(Request $request): ?PatientHistoryRecord
    {
        $id = $request->session()->get('patient_id');
        if (! $id) {
            return null;
        }

        return PatientHistoryRecord::where('patient_id', $id)
            ->whereHas('report')
            ->latest()->first();
    }

    private function normalise(string $mobile): string
    {
        return preg_replace('/[^0-9+]/', '', $mobile);
    }
}
