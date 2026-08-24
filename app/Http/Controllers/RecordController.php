<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Patient History & Record Sheet (Form 3) — the full patient profile.
 *
 * Used by BOTH the nurse and the clinic administrator (gated by the
 * fill_record_sheet permission, not by a role): each registers the complete
 * profile, and — with assign_doctors — can route the case straight to a
 * doctor or a mammographer instead of leaving it in the clinic inbox.
 */
class RecordController extends Controller
{
    private const PERSONAL_ITEMS = [
        'lumpectomy', 'biopsy', 'hyperplasia', 'hrt', 'personal_bc', 'ovarian', 'fam_ovarian', 'fam_male_bc', 'implant',
    ];

    /** Roles a case can be routed to from the registration form. */
    private const ASSIGN_ROLES = ['nurse', 'doctor', 'mammographer'];

    /** New record (register a patient). */
    public function create(): View
    {
        $clinicId = auth()->user()->clinics()->value('clinics.id');

        return view('staff.nurse.record', $this->viewData(
            new PatientHistoryRecord(['status' => PatientHistoryRecord::DRAFT]),
            new Patient(),
            $clinicId,
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $isSubmit = $request->input('action') === 'submit';
        $data = $this->validated($request, $isSubmit);

        $clinicId = auth()->user()->clinics()->value('clinics.id');
        $year = (int) now()->format('Y');
        $record = null;

        DB::transaction(function () use ($data, $isSubmit, $clinicId, $year, &$record) {
            $pc = Patient::nextPcNumber($year);

            $patient = Patient::create([
                'pc_number'       => $pc,
                'emirates_id'     => $data['emirates_id'] ?? null,
                'full_name'      => $data['full_name'],
                'dob'            => $data['dob'] ?? null,
                'nationality'    => $data['nationality'] ?? null,
                'emirate'        => $data['emirate'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'mobile1'        => $data['mobile1'],
                'mobile2'        => $data['mobile2'] ?? null,
                'email'          => $data['email'] ?? null,
                'clinic_id'      => $clinicId,
                'registered_by'  => auth()->id(),
            ]);

            $record = $this->fillRecord(new PatientHistoryRecord(), $patient, $data, $isSubmit, $clinicId, $pc);
            $record->save();

            $this->syncReferrals($record, $data);
            $this->applyAssignment($record, $data, $isSubmit);

            Audit::log($isSubmit ? 'record.submitted' : 'record.drafted', $record, "{$pc} — {$patient->full_name}");
        });

        return redirect()->route($this->homeRoute())->with('status', $this->savedMessage($record, $isSubmit));
    }

    /** Edit an existing draft. */
    public function edit(PatientHistoryRecord $record): RedirectResponse|View
    {
        $this->authorizeClinic($record);

        if (! $record->isEditableByNurse()) {
            return redirect()->route($this->homeRoute())->with('status', __('pc.record_locked'));
        }

        $record->load('patient', 'referrals');

        return view('staff.nurse.record', $this->viewData($record, $record->patient, $record->clinic_id));
    }

    public function update(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        $this->authorizeClinic($record);

        if (! $record->isEditableByNurse()) {
            return redirect()->route($this->homeRoute())->with('status', __('pc.record_locked'));
        }

        $isSubmit = $request->input('action') === 'submit';
        $data = $this->validated($request, $isSubmit);

        DB::transaction(function () use ($data, $isSubmit, $record) {
            $patient = $record->patient;
            $patient->update([
                // manual_pc_number is owned by the mammographer; this form no longer touches it.
                'emirates_id'     => $data['emirates_id'] ?? null,
                'full_name'      => $data['full_name'],
                'dob'            => $data['dob'] ?? null,
                'nationality'    => $data['nationality'] ?? null,
                'emirate'        => $data['emirate'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'mobile1'        => $data['mobile1'],
                'mobile2'        => $data['mobile2'] ?? null,
                'email'          => $data['email'] ?? null,
            ]);

            $this->fillRecord($record, $patient, $data, $isSubmit, $record->clinic_id, $record->ref_no)->save();
            $this->syncReferrals($record, $data);
            $this->applyAssignment($record, $data, $isSubmit);

            Audit::log($isSubmit ? 'record.submitted' : 'record.updated', $record, "{$record->ref_no} — {$patient->full_name}");
        });

        return redirect()->route($this->homeRoute())->with('status', $this->savedMessage($record, $isSubmit));
    }

    // ---- context (the same form serves the nurse and the clinic admin) ----

    /** Shared view props: sidebar/route context, form target and the assignment pickers. */
    private function viewData(PatientHistoryRecord $record, Patient $patient, ?int $clinicId): array
    {
        $isClinicAdmin = auth()->user()->sidebarRole() === 'clinic';

        return [
            'record'      => $record,
            'patient'     => $patient,
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => $isClinicAdmin ? 'clinic/register' : 'nurse/record',
            'formAction'  => $record->exists
                ? route('nurse.record.update', $record)
                : ($isClinicAdmin ? route('clinic.register.store') : route('nurse.record.store')),
            'backUrl'     => route($this->homeRoute()),
            'canAssign'   => auth()->user()->can('assign_doctors'),
            'assignees'   => $this->assignees($clinicId),
        ];
    }

    /** Staff of each assignable role in the record's clinic. */
    private function assignees(?int $clinicId): array
    {
        $of = fn (string $role) => $clinicId
            ? User::role($role)->whereHas('clinics', fn ($q) => $q->where('clinics.id', $clinicId))->orderBy('name')->get()
            : collect();

        return ['nurse' => $of('nurse'), 'doctor' => $of('doctor'), 'mammographer' => $of('mammographer')];
    }

    /** Where this user goes after saving (their own queue). */
    private function homeRoute(): string
    {
        return auth()->user()->sidebarRole() === 'clinic' ? 'clinic.queue' : 'nurse.queue';
    }

    /** Staff may only open records belonging to a clinic they work in. */
    private function authorizeClinic(PatientHistoryRecord $record): void
    {
        $clinicIds = auth()->user()->clinicIds();
        abort_unless(empty($clinicIds) || in_array($record->clinic_id, $clinicIds, true), 403);
    }

    private function savedMessage(?PatientHistoryRecord $record, bool $isSubmit): string
    {
        if (! $isSubmit) {
            return __('pc.draft_saved');
        }

        if ($record?->status === PatientHistoryRecord::ASSIGNED && $record->activeAssignee()) {
            return __('pc.record_submitted_assigned', [
                'role' => __('pc.role_'.$record->assigned_role),
                'name' => $record->activeAssignee()->name,
            ]);
        }

        return __('pc.record_submitted');
    }

    // ---- helpers ----

    private function validated(Request $request, bool $isSubmit): array
    {
        $rules = [
            'full_name'          => ['required', 'string', 'max:255'],
            'emirates_id'        => ['nullable', 'string', 'max:30'],
            'dob'                => ['nullable', 'date'],
            'nationality'        => ['nullable', 'string', 'max:255'],
            'emirate'            => ['nullable', 'string', 'max:255'],
            'marital_status'     => ['nullable', 'in:single,married,widow'],
            'mobile1'            => ['required', 'string', 'max:40'],
            'mobile2'            => ['nullable', 'string', 'max:40'],
            'email'              => ['nullable', 'email', 'max:255'],
            'age_at_menarche'    => ['nullable', 'integer', 'min:0', 'max:99'],
            'breast_implant'     => ['nullable', 'in:yes,no'],
            'lmp'                => ['nullable', 'date'],
            'last_mammogram'     => ['nullable', 'date'],
            'cbe_result'         => ['nullable', 'in:normal,abnormal'],
            'personal'           => ['array'],
            'personal_notes'     => ['array'],
            'family'             => ['array'],
            'refer_mammo_date'   => ['nullable', 'date'],
            'refer_mammo_hospital' => ['nullable', 'string', 'max:255'],
            'refer_uls_date'     => ['nullable', 'date'],
            'refer_uls_hospital' => ['nullable', 'string', 'max:255'],
            'consent'            => ['nullable', 'boolean'],
            'patient_signature'  => ['nullable', 'string'],
            'signed_at'          => ['nullable', 'date'],
            'action'             => ['required', 'in:draft,submit'],
            // Optional routing of the case straight from the registration form.
            'assign_role'        => ['nullable', 'in:'.implode(',', self::ASSIGN_ROLES)],
            'assignee_id'        => ['nullable', 'integer'],
            'nurse_id'           => ['nullable', 'integer'], // legacy field name for the nurse hand-off
        ];

        if ($isSubmit) {
            $rules['consent'] = ['accepted'];                 // consent required on submit
            $rules['patient_signature'] = ['required', 'string']; // patient signature required on submit
        }

        return $request->validate($rules);
    }

    private function fillRecord(PatientHistoryRecord $record, Patient $patient, array $data, bool $isSubmit, ?int $clinicId, string $ref): PatientHistoryRecord
    {
        // Personal history: yes/no answers + a conditional detail note per "yes".
        $personal = [];
        $personalNotes = [];
        foreach (self::PERSONAL_ITEMS as $item) {
            $answer = ($data['personal'][$item] ?? 'no') === 'yes' ? 'yes' : 'no';
            $personal[$item] = $answer;
            $note = trim((string) ($data['personal_notes'][$item] ?? ''));
            if ($answer === 'yes' && $note !== '') {
                $personalNotes[$item] = $note;
            }
        }

        // Family history: one entry per degree — { relationship, age at diagnosis }.
        $family = [];
        foreach (['deg1', 'deg2', 'deg3'] as $deg) {
            $rel = $data['family'][$deg]['relationship'] ?? null;
            $age = $data['family'][$deg]['age'] ?? null;
            if ($rel || ($age !== null && $age !== '')) {
                $family[$deg] = [
                    'relationship' => $rel ?: null,
                    'age'          => ($age !== null && $age !== '') ? (int) $age : null,
                ];
            }
        }

        $record->fill([
            'ref_no'                 => $ref,
            'patient_id'             => $patient->id,
            'clinic_id'              => $clinicId,
            // Whoever the case belongs to on the nursing side — a nurse chosen in the
            // assignment section keeps ownership; otherwise it is whoever filled the form.
            'nurse_id'               => $record->nurse_id ?: auth()->id(),
            'record_date'            => $record->record_date ?? now()->toDateString(),
            'age_at_menarche'        => $data['age_at_menarche'] ?? null,
            'breast_implant'         => $data['breast_implant'] ?? null,
            'lmp'                    => $data['lmp'] ?? null,
            'personal_history'       => $personal,
            'personal_history_notes' => $personalNotes ?: null,
            'family_history'         => $family ?: null,
            'last_mammogram'         => $data['last_mammogram'] ?? null,
            'cbe_result'             => $data['cbe_result'] ?? null,
            'examiner_name'          => auth()->user()->name,
            'consent_given'          => (bool) ($data['consent'] ?? false),
            'consent_at'             => ($data['consent'] ?? false) ? now() : null,
            'consent_statements'     => ($data['consent'] ?? false) ? __('pc.consent_statements') : null,
            'patient_signature'      => $data['patient_signature'] ?? null,
            'signed_at'              => $data['signed_at'] ?? null,
        ]);

        if ($isSubmit) {
            $record->status = PatientHistoryRecord::SUBMITTED;
            $record->submitted_at = now();
        } else {
            $record->status = PatientHistoryRecord::DRAFT;
        }

        return $record;
    }

    /**
     * Route the case from the registration form (needs assign_doctors).
     *
     * nurse                 → hands the record to that nurse to complete/verify (status unchanged).
     * doctor / mammographer → records the choice; on submit the case goes straight to ASSIGNED
     *                         instead of waiting unassigned in the clinic admin's inbox.
     */
    private function applyAssignment(PatientHistoryRecord $record, array $data, bool $isSubmit): void
    {
        if (! auth()->user()->can('assign_doctors')) {
            return;
        }

        $role = $data['assign_role'] ?? null;
        // The nurse hand-off can also arrive as a bare nurse_id (quick intake).
        $assigneeId = $data['assignee_id'] ?? null;
        if (! $role && ! empty($data['nurse_id'])) {
            $role = 'nurse';
            $assigneeId = $data['nurse_id'];
        }

        if (! $role || ! $assigneeId) {
            // "Keep in the clinic inbox" — drop any earlier routing while the case is still open.
            if (in_array($record->status, [PatientHistoryRecord::DRAFT, PatientHistoryRecord::SUBMITTED], true)) {
                $record->forceFill([
                    'assigned_role'      => null,
                    'assigned_doctor_id' => null,
                    'mammographer_id'    => null,
                ])->save();
            }

            return;
        }

        $assignee = User::role($role)
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $record->clinic_id))
            ->find($assigneeId);

        if (! $assignee) {
            throw ValidationException::withMessages(['assignee_id' => __('pc.assignee_not_in_clinic')]);
        }

        if ($role === 'nurse') {
            $record->forceFill(['nurse_id' => $assignee->id])->save();
            Audit::log('record.assigned', $record, "{$record->ref_no} → ".__('pc.role_nurse').": {$assignee->name}");

            return;
        }

        $record->forceFill([
            'assigned_role' => $role,
            ...($role === PatientHistoryRecord::ROLE_DOCTOR
                ? ['assigned_doctor_id' => $assignee->id, 'mammographer_id' => null]
                : ['mammographer_id' => $assignee->id, 'assigned_doctor_id' => null]),
            // Only a submitted record actually moves into the assignee's queue.
            ...($isSubmit ? ['status' => PatientHistoryRecord::ASSIGNED] : []),
        ])->save();

        if ($isSubmit) {
            Audit::log('record.assigned', $record, "{$record->ref_no} → ".__('pc.role_'.$role).": {$assignee->name}");
        }
    }

    /** Recreate referral rows from the abnormal-result referral fields. */
    private function syncReferrals(PatientHistoryRecord $record, array $data): void
    {
        $record->referrals()->delete();

        if (($data['cbe_result'] ?? null) !== 'abnormal') {
            return;
        }

        if (! empty($data['refer_mammo_date']) || ! empty($data['refer_mammo_hospital'])) {
            $record->referrals()->create([
                'type' => 'mammogram', 'referral_date' => $data['refer_mammo_date'] ?? null, 'hospital' => $data['refer_mammo_hospital'] ?? null,
            ]);
        }
        if (! empty($data['refer_uls_date']) || ! empty($data['refer_uls_hospital'])) {
            $record->referrals()->create([
                'type' => 'uls', 'referral_date' => $data['refer_uls_date'] ?? null, 'hospital' => $data['refer_uls_hospital'] ?? null,
            ]);
        }
    }
}
