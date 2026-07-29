<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingServiceRequest;
use Illuminate\Database\Eloquent\Builder;

/**
 * Computes service-booking metrics for the Bookings dashboard, with optional
 * filters (status, service type, emirate, date range on submission date).
 */
class BookingDashboardService
{
    /** @return Builder<Booking> */
    public static function baseQuery(array $filters = []): Builder
    {
        $q = Booking::query();

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['emirate'])) {
            $q->where('emirate', $filters['emirate']);
        }
        if (! empty($filters['service_type'])) {
            $q->whereHas('services', fn ($s) => $s->where('service_type', $filters['service_type']));
        }
        if (! empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        return $q;
    }

    public static function stats(array $filters = []): array
    {
        $byStatus = (clone self::baseQuery($filters))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $count = fn (string $s) => (int) ($byStatus[$s] ?? 0);
        $new       = $count(Booking::STATUS_NEW);
        $approved  = $count(Booking::STATUS_APPROVED);
        $paid      = $count(Booking::STATUS_PAID);
        $completed = $count(Booking::STATUS_COMPLETED);
        $rejected  = $count(Booking::STATUS_REJECTED);
        $total     = $new + $approved + $paid + $completed + $rejected;

        $revenue = (float) (clone self::baseQuery($filters))
            ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_COMPLETED])
            ->sum('payment_amount');

        // Of the requests that weren't rejected, how many reached completion.
        $processed      = $total - $rejected;
        $completionRate = $processed ? round($completed / $processed * 100, 1) : 0;

        return [
            'total'          => $total,
            'new'            => $new,
            'approved'       => $approved,
            'paid'           => $paid,
            'completed'      => $completed,
            'rejected'       => $rejected,
            'pending'        => $new + $approved + $paid,   // in-flight, not yet completed/rejected
            'revenue'        => $revenue,
            'completionRate' => $completionRate,
            'byStatus'       => self::byStatus($count, $total),
            'funnel'         => self::funnel($new, $approved, $paid, $completed, $total),
            'byService'      => self::byService($filters),
            'byEmirate'      => self::byEmirate($filters),
        ];
    }

    /** Status distribution rows (label, count, %, colours) for the legend/breakdown. */
    private static function byStatus(callable $count, int $total): array
    {
        $out = [];
        foreach (Booking::statuses() as $status) {
            $c = $count($status);
            if ($c === 0 && $status === Booking::STATUS_REJECTED) {
                // hide an empty "rejected" row unless there are rejections
                continue;
            }
            [$color, $bg] = Booking::colorsFor($status);
            $out[] = [
                'name'  => __('pc.booking_status_'.$status),
                'val'   => number_format($c),
                'pct'   => $total ? round($c / $total * 100).'%' : '0%',
                'color' => $color,
                'bg'    => $bg,
            ];
        }
        return $out;
    }

    /** The review → approve → pay → complete funnel (cumulative reach at each stage). */
    private static function funnel(int $new, int $approved, int $paid, int $completed, int $total): array
    {
        $submitted    = $total;                          // everything received
        $reachApprove = $approved + $paid + $completed;  // approved or further
        $reachPaid    = $paid + $completed;              // paid or further
        $reachDone    = $completed;
        $max          = max(1, $submitted);

        $row = fn (string $key, int $val, string $color) => [
            'name'  => __('pc.booking_funnel_'.$key),
            'val'   => number_format($val),
            'pct'   => round($val / $max * 100).'%',
            'color' => $color,
        ];

        return [
            $row('submitted', $submitted,    '#B26A00'),
            $row('approved',  $reachApprove, '#2A6FDB'),
            $row('paid',      $reachPaid,    '#7E4CC4'),
            $row('completed', $reachDone,    '#2E7D32'),
        ];
    }

    /** Requested-service breakdown (A–D) across the filtered bookings. */
    private static function byService(array $filters): array
    {
        $ids = (clone self::baseQuery($filters))->pluck('id');

        $rows = BookingServiceRequest::whereIn('booking_id', $ids)
            ->selectRaw('service_type, count(*) as c')->groupBy('service_type')
            ->pluck('c', 'service_type')->all();

        $meta = [
            'A' => ['label' => __('pc.svc_a_title'), 'color' => '#16A6A6'],
            'B' => ['label' => __('pc.svc_b_title'), 'color' => '#2A6FDB'],
            'C' => ['label' => __('pc.svc_c_title'), 'color' => '#7E4CC4'],
            'D' => ['label' => __('pc.svc_d_title'), 'color' => '#F7941E'],
        ];
        $max = max(array_merge([1], array_values($rows)));

        $out = [];
        foreach ($meta as $type => $m) {
            $c = (int) ($rows[$type] ?? 0);
            $out[] = [
                'name'  => $m['label'],
                'val'   => number_format($c),
                'pct'   => round($c / $max * 100).'%',
                'color' => $m['color'],
            ];
        }
        return $out;
    }

    /** Booking counts by emirate across the filtered set. */
    private static function byEmirate(array $filters): array
    {
        $rows = (clone self::baseQuery($filters))
            ->selectRaw('emirate, count(*) as c')->groupBy('emirate')
            ->orderByDesc('c')->pluck('c', 'emirate')->all();

        $max = max(array_merge([1], array_values($rows)));
        $tints = ['#E6017E', '#16A6A6', '#2A6FDB', '#7E4CC4', '#F7941E', '#43A047'];

        $out = [];
        $i = 0;
        foreach ($rows as $em => $c) {
            $out[] = [
                'name'  => $em ? __('pc.em_'.$em) : '—',
                'val'   => number_format($c),
                'pct'   => round($c / $max * 100).'%',
                'color' => $tints[$i++ % count($tints)],
            ];
        }
        return $out;
    }
}
