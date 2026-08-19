<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientHistoryRecord extends Model
{
    // Workflow statuses. The clinic admin is the routing hub: they assign a case to a
    // doctor or a mammographer, that role works it, and it comes back to the admin
    // (RETURNED) to be reassigned to the other role or closed (COMPLETED).
    public const DRAFT      = 'draft';
    public const SUBMITTED  = 'submitted';   // submitted by nurse, in the admin inbox for first assignment
    public const ASSIGNED   = 'assigned';    // assigned to a role+person, awaiting them to start
    public const IN_REVIEW  = 'in_review';   // the assigned person is working on it
    public const RETURNED   = 'returned';    // role finished, back in the admin inbox for a decision
    public const COMPLETED  = 'completed';   // admin closed the case (terminal)
    public const REPORT_SENT = 'report_sent'; // legacy: mammogram report sent (superseded by report_sent_at + RETURNED)

    // Roles a case can be assigned to.
    public const ROLE_DOCTOR       = 'doctor';
    public const ROLE_MAMMOGRAPHER = 'mammographer';

    protected $fillable = [
        'ref_no', 'patient_id', 'clinic_id', 'nurse_id', 'assigned_doctor_id', 'mammographer_id', 'assigned_role', 'record_date',
        'age_at_menarche', 'number_of_children', 'breast_implant', 'age_first_delivery', 'lmp', 'menopause', 'menopause_since_year',
        'personal_history', 'personal_history_notes', 'family_history',
        'breastfeeding_under6', 'breastfeeding_children', 'last_mammogram', 'cbe_result', 'examiner_name',
        'consent_given', 'consent_at', 'consent_statements', 'patient_signature', 'signed_at',
        'mammogram_report_path', 'report_uploaded_at', 'report_sent_at',
        'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'record_date'            => 'date',
            'lmp'                    => 'date',
            'last_mammogram'         => 'date',
            'signed_at'              => 'date',
            'menopause'              => 'boolean',
            'breastfeeding_under6'   => 'boolean',
            'consent_given'          => 'boolean',
            'consent_at'             => 'datetime',
            'submitted_at'           => 'datetime',
            'report_uploaded_at'     => 'datetime',
            'report_sent_at'         => 'datetime',
            'personal_history'       => 'array',
            'personal_history_notes' => 'array',
            'family_history'         => 'array',
            'consent_statements'     => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function mammographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mammographer_id');
    }

    public function examination(): HasOne
    {
        return $this->hasOne(ClinicalExamination::class, 'record_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'record_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class, 'record_id');
    }

    public function isEditableByNurse(): bool
    {
        return $this->status === self::DRAFT;
    }

    /** The user the case is currently assigned to (doctor or mammographer), if any. */
    public function activeAssignee(): ?User
    {
        return match ($this->assigned_role) {
            self::ROLE_DOCTOR       => $this->doctor,
            self::ROLE_MAMMOGRAPHER => $this->mammographer,
            default                 => null,
        };
    }

    /** Final result comes from the doctor's exam, falling back to the nurse's initial CBE result. */
    public function finalResult(): ?string
    {
        return $this->examination?->result ?? $this->cbe_result;
    }
}
