<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalExamination extends Model
{
    protected $fillable = [
        'record_id', 'doctor_id', 'exam_date', 'venue_clinic_id',
        'symptoms', 'other_symptoms', 'signs', 'skin_rash_discharge_type', 'other_signs',
        'pins', 'cbe_result', 'comments', 'other_findings', 'recommendation', 'result',
        'examiner_name', 'attested_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'exam_date'   => 'date',
            'attested_at' => 'datetime',
            'symptoms'    => 'array',
            'signs'       => 'array',
            'pins'        => 'array',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(PatientHistoryRecord::class, 'record_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
