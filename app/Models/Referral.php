<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'record_id', 'type', 'hospital', 'referral_date', 'status', 'result', 'result_date',
    ];

    protected function casts(): array
    {
        return [
            'referral_date' => 'date',
            'result_date'   => 'date',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(PatientHistoryRecord::class, 'record_id');
    }
}
