<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Clinic admin — quick patient intake. Captures the Emirates-ID demographics and
 * hands the case to a nurse (as a draft) to complete the full record sheet.
 */
class ClinicPatientController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name'   => ['required', 'string', 'max:255'],
            'emirates_id' => ['nullable', 'string', 'max:30'],
            'dob'         => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'emirate'     => ['nullable', 'string', 'max:40'],
            'mobile1'     => ['required', 'string', 'max:40'],
            'nurse_id'    => ['required', Rule::exists('users', 'id')],
        ]);

        // The record's clinic is the admin's clinic; the assigned nurse must belong to it.
        $clinicId = auth()->user()->clinics()->value('clinics.id');
        abort_unless($clinicId, 403);

        $nurse = User::role('nurse')
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $clinicId))
            ->findOrFail($data['nurse_id']);

        $record = null;
        DB::transaction(function () use ($data, $clinicId, $nurse, &$record) {
            $pc = Patient::nextPcNumber((int) now()->format('Y'));

            $patient = Patient::create([
                'pc_number'     => $pc,
                'emirates_id'   => $data['emirates_id'] ?? null,
                'full_name'     => $data['full_name'],
                'dob'           => $data['dob'] ?? null,
                'nationality'   => $data['nationality'] ?? null,
                'emirate'       => $data['emirate'] ?? null,
                'mobile1'       => $data['mobile1'],
                'clinic_id'     => $clinicId,
                'registered_by' => auth()->id(),
            ]);

            // Draft record handed to the nurse to complete the full record sheet.
            $record = PatientHistoryRecord::create([
                'ref_no'      => $pc,
                'patient_id'  => $patient->id,
                'clinic_id'   => $clinicId,
                'nurse_id'    => $nurse->id,
                'record_date' => now()->toDateString(),
                'status'      => PatientHistoryRecord::DRAFT,
            ]);

            Audit::log('patient.registered', $record, "{$pc} — {$patient->full_name} → {$nurse->name}");
        });

        return redirect()->route('clinic.queue')->with('status', __('pc.patient_registered_ok'));
    }
}
