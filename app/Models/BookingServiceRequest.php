<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingServiceRequest extends Model
{
    protected $fillable = [
        'booking_id', 'service_type', 'selection', 'add_team',
        'event_date', 'start_time', 'end_time', 'venue',
    ];

    protected function casts(): array
    {
        return [
            'add_team'   => 'boolean',
            'event_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
