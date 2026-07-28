<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'pc_number', 'manual_pc_number', 'emirates_id', 'full_name', 'dob', 'nationality', 'emirate', 'marital_status',
        'mobile1', 'mobile2', 'email', 'clinic_id', 'registered_by',
    ];

    protected function casts(): array
    {
        return ['dob' => 'date'];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function record(): HasOne
    {
        return $this->hasOne(PatientHistoryRecord::class);
    }

    /** Generate the next PC number for the given year: PC-YYYY-###### (6-digit sequence). */
    public static function nextPcNumber(int $year): string
    {
        $prefix = "PC-{$year}-";
        $last = static::where('pc_number', 'like', $prefix.'%')->max('pc_number');
        $seq  = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
