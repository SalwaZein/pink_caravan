<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    // Service-booking review lifecycle.
    public const STATUS_NEW       = 'new';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_PAID      = 'paid';
    public const STATUS_COMPLETED = 'completed';

    /** New bookings start in the review queue even before they hit the DB default. */
    protected $attributes = ['status' => self::STATUS_NEW];

    protected $fillable = [
        'ref_no', 'organisation', 'contact_person', 'email', 'mobile',
        'emirate', 'estimated_participants', 'notes', 'status',
        'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at',
        'rejection_reason', 'rejected_at',
        'paid_by', 'paid_at', 'payment_amount', 'payment_reference',
        'completed_by', 'completed_at', 'completion_report_path', 'completion_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at'    => 'datetime',
            'approved_at'    => 'datetime',
            'rejected_at'    => 'datetime',
            'paid_at'        => 'datetime',
            'completed_at'   => 'datetime',
            'payment_amount' => 'decimal:2',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(BookingServiceRequest::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** Translation key for the current status pill. */
    public function statusLabel(): string
    {
        return __('pc.booking_status_'.$this->status);
    }

    /** [text, background] colours for the current status pill. */
    public function statusColors(): array
    {
        return self::colorsFor($this->status);
    }

    /** [text, background] colours for a given status — shared by pills and the dashboard. */
    public static function colorsFor(?string $status): array
    {
        return match ($status) {
            self::STATUS_APPROVED  => ['#2A6FDB', '#E6EEFB'],
            self::STATUS_PAID      => ['#7E4CC4', '#F0E9FA'],
            self::STATUS_COMPLETED => ['#2E7D32', '#E4F4EF'],
            self::STATUS_REJECTED  => ['#C0392B', '#FBE9E7'],
            default                => ['#B26A00', '#FBF0DC'], // new / awaiting review
        };
    }

    /** The lifecycle statuses in workflow order. */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW, self::STATUS_APPROVED, self::STATUS_PAID,
            self::STATUS_COMPLETED, self::STATUS_REJECTED,
        ];
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
