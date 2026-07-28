<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'ref_no', 'organisation', 'contact_person', 'email', 'mobile',
        'emirate', 'estimated_participants', 'notes', 'status',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(BookingServiceRequest::class);
    }

    /** Generate the next booking ref: PCB-YYYY-#### */
    public static function nextRef(int $year): string
    {
        $prefix = "PCB-{$year}-";
        $last = static::where('ref_no', 'like', $prefix.'%')->max('ref_no');
        $seq  = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
