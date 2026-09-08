<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\QueueToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Issuing and advancing walk-in queue tokens (business feedback #7).
 *
 * Numbering is scoped to a clinic (the location) and a day, so concurrent
 * mobile clinics never collide and each morning restarts at #1.
 */
class QueueService
{
    /**
     * Issue the next token for this clinic today and WhatsApp it to the visitor.
     * The number is allocated inside a transaction with a row lock on the day's
     * tokens, so two volunteers registering at once cannot get the same number.
     */
    public static function issue(Clinic $clinic, string $name, string $whatsapp, ?int $issuedBy = null, ?Carbon $date = null): QueueToken
    {
        $date = $date ?? now()->startOfDay();

        return DB::transaction(function () use ($clinic, $name, $whatsapp, $issuedBy, $date) {
            $last = QueueToken::forDesk($clinic->id, $date)
                ->lockForUpdate()
                ->max('number');

            $number = ((int) $last) + 1;

            $token = QueueToken::create([
                'clinic_id'       => $clinic->id,
                'queue_date'      => $date->toDateString(),
                'number'          => $number,
                'code'            => self::code($clinic, $number),
                'patient_name'    => $name,
                'whatsapp_number' => Messenger::normalise($whatsapp) ?? $whatsapp,
                'status'          => QueueToken::WAITING,
                'issued_by'       => $issuedBy,
            ]);

            return $token;
        });
    }

    /** Display code: the clinic code plus a zero-padded arrival number. */
    public static function code(Clinic $clinic, int $number): string
    {
        $prefix = $clinic->code ?: 'PC';

        return $prefix.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * The next visitor still waiting after the given token — the one to warn
     * when the current person walks into the clinic.
     */
    public static function nextUpcoming(QueueToken $after): ?QueueToken
    {
        return QueueToken::forDesk($after->clinic_id, $after->queue_date)
            ->whereIn('status', QueueToken::UPCOMING)
            ->where('number', '>', $after->number)
            ->orderBy('number')
            ->first();
    }

    /** Live counts for the desk header. */
    public static function stats(int $clinicId, Carbon|string $date): array
    {
        $rows = QueueToken::forDesk($clinicId, $date)
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $of = fn (string ...$s) => collect($s)->sum(fn ($k) => (int) ($rows[$k] ?? 0));

        return [
            'total'    => (int) $rows->sum(),
            'waiting'  => $of(QueueToken::WAITING, QueueToken::NOTIFIED),
            'called'   => $of(QueueToken::CALLED, QueueToken::IN_CLINIC),
            'served'   => $of(QueueToken::SERVED),
            'dropped'  => $of(QueueToken::NO_SHOW, QueueToken::CANCELLED),
        ];
    }
}
