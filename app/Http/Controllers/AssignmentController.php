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
    /**
     * Clinic admin assigns (or reassigns) a case to a doctor or a mammographer in the
     * same clinic. The case moves to ASSIGNED and waits for that person to start.
     */
    public function assign(Request $request): RedirectResponse
    {
        $clinicIds = auth()->user()->clinicIds();

        $data = $request->validate([
            'record_id' => [
                'required',
                Rule::exists('patient_history_records', 'id')->whereIn('clinic_id', $clinicIds ?: [0]),
            ],
            'role'        => ['required', Rule::in([PatientHistoryRecord::ROLE_DOCTOR, PatientHistoryRecord::ROLE_MAMMOGRAPHER])],
            'assignee_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $record = PatientHistoryRecord::findOrFail($data['record_id']);

        // The assignee must hold the chosen role AND belong to the record's clinic.
        $assignee = User::role($data['role'])
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $record->clinic_id))
            ->findOrFail($data['assignee_id']);

        $record->update([
            'assigned_role' => $data['role'],
            'status'        => PatientHistoryRecord::ASSIGNED,
            ...($data['role'] === PatientHistoryRecord::ROLE_DOCTOR
                ? ['assigned_doctor_id' => $assignee->id]
                : ['mammographer_id' => $assignee->id]),
        ]);

        $roleLabel = __('pc.role_'.$data['role']);
        Audit::log('record.assigned', $record, "{$record->ref_no} → {$roleLabel}: {$assignee->name}");

        return redirect()->route('clinic.assign')->with('status', __('pc.assigned_ok'));
    }

    /** Clinic admin closes a case that a role has finished and returned. */
    public function complete(PatientHistoryRecord $record): RedirectResponse
    {
        $clinicIds = auth()->user()->clinicIds();
        abort_unless(empty($clinicIds) || in_array($record->clinic_id, $clinicIds, true), 403);
        abort_unless($record->status === PatientHistoryRecord::RETURNED, 403);

        $record->update(['status' => PatientHistoryRecord::COMPLETED]);

        Audit::log('record.completed', $record, $record->ref_no);

        return redirect()->route('clinic.assign')->with('status', __('pc.case_completed_ok'));
    }
}
