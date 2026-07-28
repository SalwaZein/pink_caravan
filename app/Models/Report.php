<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = ['record_id', 'verify_code', 'path', 'generated_at', 'delivery'];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'delivery'     => 'array',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(PatientHistoryRecord::class, 'record_id');
    }
}
