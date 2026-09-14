<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The mammographer's findings for one case (business feedback #5).
 *
 * Kept apart from the uploaded mammogram report PDF: the findings are recorded
 * at the visit, while the formal report may only be ready days later (#6).
 *
 * Round 3 trimmed the form down to a single free-text `findings` box; the
 * structured columns (modality, density, BI-RADS, impression…) stay on the table
 * so records filed with the earlier form still read back correctly.
 */
class MammogramFinding extends Model
{
    public const DRAFT     = 'draft';
    public const SUBMITTED = 'submitted';

    /** ACR breast-density categories. */
    public const DENSITIES = ['a', 'b', 'c', 'd'];

    /** BI-RADS assessment categories. */
    public const BIRADS = ['0', '1', '2', '3', '4', '5', '6'];

    public const MODALITIES = ['mammogram', 'ultrasound', 'both'];

    public const RECOMMENDATIONS = ['routine', 'short_interval', 'additional_imaging', 'biopsy', 'referral'];

    protected $fillable = [
        // `findings` is the one free-text box the mammographer fills in (business
        // feedback round 3). The columns below it are kept for records filed with
        // the earlier, longer form.
        'record_id', 'mammographer_id', 'findings',
        'exam_date', 'modality', 'breast_density',
        'birads_right', 'birads_left', 'findings_right', 'findings_left',
        'comparison', 'impression', 'recommendation', 'notes',
        'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_date'    => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(PatientHistoryRecord::class, 'record_id');
    }

    public function mammographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mammographer_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::SUBMITTED;
    }

    /** The higher of the two sides — what drives the overall assessment. */
    public function highestBirads(): ?string
    {
        $values = array_filter([$this->birads_right, $this->birads_left], fn ($v) => $v !== null && $v !== '');

        if ($values === []) {
            return null;
        }

        // BI-RADS 0 means "incomplete, needs more imaging" — it outranks 1 and 2 clinically.
        $rank = fn (string $v) => $v === '0' ? 3.5 : (float) $v;

        return collect($values)->sortByDesc($rank)->first();
    }
}
