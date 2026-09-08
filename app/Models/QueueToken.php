<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One visitor's place in a clinic's walk-in queue (business feedback #7).
 *
 * Tokens are numbered per clinic (the location) per day, so every mobile clinic
 * running on the same morning has its own #1. The lifecycle is:
 *   waiting → notified ("your turn is approaching") → called → in_clinic → served
 * with no_show / cancelled as the exits.
 */
class QueueToken extends Model
{
    public const WAITING   = 'waiting';
    public const NOTIFIED  = 'notified';
    public const CALLED    = 'called';
    public const IN_CLINIC = 'in_clinic';
    public const SERVED    = 'served';
    public const NO_SHOW   = 'no_show';
    public const CANCELLED = 'cancelled';

    /** Still somewhere in the queue (not finished and not dropped). */
    public const OPEN = [self::WAITING, self::NOTIFIED, self::CALLED, self::IN_CLINIC];

    /** Not yet called in — eligible for the "approaching" heads-up. */
    public const UPCOMING = [self::WAITING, self::NOTIFIED];

    protected $fillable = [
        'clinic_id', 'queue_date', 'number', 'code', 'patient_name', 'whatsapp_number',
        'patient_id', 'status', 'issued_by', 'called_by',
        'notified_at', 'called_at', 'entered_at', 'served_at', 'closed_at',
        'delivery', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'queue_date'  => 'date',
            'notified_at' => 'datetime',
            'called_at'   => 'datetime',
            'entered_at'  => 'datetime',
            'served_at'   => 'datetime',
            'closed_at'   => 'datetime',
            'delivery'    => 'array',
        ];
    }

    protected $attributes = ['status' => self::WAITING];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    // ---- scopes ----

    /** One clinic's queue for one day — the only view the desk ever needs. */
    public function scopeForDesk(Builder $q, int $clinicId, Carbon|string $date): Builder
    {
        return $q->where('clinic_id', $clinicId)
            ->whereDate('queue_date', $date instanceof Carbon ? $date->toDateString() : $date);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN);
    }

    // ---- helpers ----

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    /** How many people are still ahead of this token. */
    public function aheadCount(): int
    {
        return static::forDesk($this->clinic_id, $this->queue_date)
            ->whereIn('status', self::OPEN)
            ->where('number', '<', $this->number)
            ->count();
    }

    public function statusLabel(): string
    {
        return __('pc.qt_'.$this->status);
    }

    /** Pill colours for the queue board, keyed by status. */
    public static function colorsFor(string $status): array
    {
        return [
            self::WAITING   => ['c' => '#6B4257', 'bg' => '#F1E7ED'],
            self::NOTIFIED  => ['c' => '#B25E00', 'bg' => '#FBEEDD'],
            self::CALLED    => ['c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            self::IN_CLINIC => ['c' => '#7E4CC4', 'bg' => '#EEE6FA'],
            self::SERVED    => ['c' => '#2E7D32', 'bg' => '#E4F4EF'],
            self::NO_SHOW   => ['c' => '#C62828', 'bg' => '#FBE4E4'],
            self::CANCELLED => ['c' => '#9A8F97', 'bg' => '#F4EEF1'],
        ][$status] ?? ['c' => '#6B4257', 'bg' => '#F1E7ED'];
    }
}
