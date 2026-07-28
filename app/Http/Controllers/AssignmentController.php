<?php

namespace App\Http\Controllers;

use App\Models\PatientHistoryRecord;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /** Clinic admin assigns a submitted record to a doctor in the same clinic. */
    public function assign(Request $request): RedirectResponse
    {
        $clinicIds = auth()->user()->clinicIds();

        $data = $request->validate([
            'record_id' => [
                'required',
                Rule::exists('patient_history_records', 'id')->whereIn('clinic_id', $clinicIds ?: [0]),
            ],
            'doctor_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $record = PatientHistoryRecord::findOrFail($data['record_id']);

        // The doctor must belong to the record's clinic.
        $doctor = User::role('doctor')
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $record->clinic_id))
            ->findOrFail($data['doctor_id']);

        $record->update([
            'assigned_doctor_id' => $doctor->id,
            'status'             => PatientHistoryRecord::ASSIGNED,
        ]);

        Audit::log('record.assigned', $record, "{$record->ref_no} → {$doctor->name}");

        return redirect()->route('clinic.assign')->with('status', __('pc.assigned_ok'));
    }
}
