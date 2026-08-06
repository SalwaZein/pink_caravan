<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientHistoryRecord;
use App\Models\Referral;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Nurse / Medical Admin — Patient History & Record Sheet (Form 3).
 * Handles patient registration + the full assessment with save-as-draft and submit.
 */
class RecordController extends Controller
{
    private const PERSONAL_ITEMS = [
        'lumpectomy', 'biopsy', 'hyperplasia', 'hrt', 'personal_bc', 'ovarian', 'fam_ovarian', 'fam_male_bc', 'implant',
    ];

    /** New record (register a patient). */
    public function create(): View
    {
        return view('staff.nurse.record', [
            'record'      => new PatientHistoryRecord(['status' => PatientHistoryRecord::DRAFT]),
            'patient'     => new Patient(),
            'sidebarRole' => 'nurse',
            'route'       => 'nurse/record',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isSubmit = $request->input('action') === 'submit';
        $data = $this->validated($request, $isSubmit);

        $clinicId = auth()->user()->clinics()->value('clinics.id');
        $year = (int) now()->format('Y');

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

            Audit::log($isSubmit ? 'record.submitted' : 'record.drafted', $record, "{$pc} — {$patient->full_name}");
        });

        return redirect()->route('nurse.queue')->with(
            'status',
            $isSubmit ? __('pc.record_submitted') : __('pc.draft_saved'),
        );
    }

    /** Edit an existing draft. */
    public function edit(PatientHistoryRecord $record): RedirectResponse|View
    {
        if (! $record->isEditableByNurse()) {
            return redirect()->route('nurse.queue')->with('status', __('pc.record_locked'));
        }

        return view('staff.nurse.record', [
            'record'      => $record->load('patient', 'referrals'),
            'patient'     => $record->patient,
            'sidebarRole' => 'nurse',
            'route'       => 'nurse/record',
        ]);
    }

    public function update(Request $request, PatientHistoryRecord $record): RedirectResponse
    {
        if (! $record->isEditableByNurse()) {
            return redirect()->route('nurse.queue')->with('status', __('pc.record_locked'));
        }

        $isSubmit = $request->input('action') === 'submit';
        $data = $this->validated($request, $isSubmit);

        DB::transaction(function () use ($data, $isSubmit, $record) {
            $patient = $record->patient;
            $patient->update([
                // manual_pc_number is owned by the mammographer; the nurse form no longer touches it.
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

            Audit::log($isSubmit ? 'record.submitted' : 'record.updated', $record, "{$record->ref_no} — {$patient->full_name}");
        });

        return redirect()->route('nurse.queue')->with(
            'status',
            $isSubmit ? __('pc.record_submitted') : __('pc.draft_saved'),
        );
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
            'nurse_id'               => auth()->id(),
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
