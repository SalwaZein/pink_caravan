<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\PatientHistoryRecord;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Builder;

/**
 * Computes real campaign metrics from patient records, with optional filters
 * (emirate, clinic, clinic type, doctor, date range).
 */
class DashboardService
{
    /** @return Builder<PatientHistoryRecord> */
    public static function baseQuery(array $filters = [], ?array $clinicIds = null): Builder
    {
        $q = PatientHistoryRecord::query()->with('patient');

        if ($clinicIds !== null) {
            $q->whereIn('clinic_id', $clinicIds ?: [0]);
        }
        if (! empty($filters['clinic_id'])) {
            $q->where('clinic_id', $filters['clinic_id']);
        }
        if (! empty($filters['doctor_id'])) {
            $q->where('assigned_doctor_id', $filters['doctor_id']);
        }
        if (! empty($filters['type'])) {
            $q->whereHas('clinic', fn ($c) => $c->where('type', $filters['type']));
        }
        if (! empty($filters['emirate'])) {
            $q->whereHas('patient', fn ($p) => $p->where('emirate', $filters['emirate']));
        }
        if (! empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        return $q;
    }

    public static function stats(array $filters = [], ?array $clinicIds = null): array
    {
        $ids = (clone self::baseQuery($filters, $clinicIds))->pluck('id');

        $total     = $ids->count();
        // "Completed" screening includes records whose report has since been sent.
        $completed = (clone self::baseQuery($filters, $clinicIds))->whereIn('status', ['completed', 'report_sent'])->count();
        $pending   = (clone self::baseQuery($filters, $clinicIds))->whereIn('status', ['submitted', 'assigned', 'in_review'])->count();

        // Results come from the doctor's exam.
        $resultQuery = fn (string $res) => \App\Models\ClinicalExamination::whereIn('record_id', $ids)->where('result', $res)->count();
        $normal   = $resultQuery('normal');
        $abnormal = $resultQuery('abnormal');
        $resultTotal = $normal + $abnormal;

        $referrals       = Referral::whereIn('record_id', $ids)->count();
        $referralsClosed = Referral::whereIn('record_id', $ids)->where('status', 'completed')->count();

        return [
            'total'          => $total,
            'completed'      => $completed,
            'pending'        => $pending,
            'normal'         => $normal,
            'abnormal'       => $abnormal,
            'abnormalRate'   => $resultTotal ? round($abnormal / $resultTotal * 100, 1) : 0,
            'normalPct'      => $resultTotal ? round($normal / $resultTotal * 100, 1) : 0,
            'referrals'      => $referrals,
            'referralsClosed'=> $referralsClosed,
            'byEmirate'      => self::byEmirate($ids),
            'byClinic'       => self::byClinic($ids),
        ];
    }

    private static function byEmirate($recordIds): array
    {
        $rows = PatientHistoryRecord::whereIn('patient_history_records.id', $recordIds)
            ->join('patients', 'patients.id', '=', 'patient_history_records.patient_id')
            ->selectRaw('patients.emirate as emirate, count(*) as c')
            ->groupBy('patients.emirate')
            ->pluck('c', 'emirate')->all();

        $max = max(array_merge([1], array_values($rows)));
        $tints = ['#16A6A6', '#2A6FDB', '#7E4CC4', '#F7941E', '#E6017E', '#43A047'];
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

    private static function byClinic($recordIds): array
    {
        $counts = PatientHistoryRecord::whereIn('id', $recordIds)
            ->selectRaw('clinic_id, count(*) as c')->groupBy('clinic_id')->pluck('c', 'clinic_id')->all();

        $typeMeta = [
            'fixed'  => ['label' => __('pc.fixed'),       'c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            'mobile' => ['label' => __('pc.mobile_type'), 'c' => '#16A6A6', 'bg' => '#DBF1EE'],
            'mini'   => ['label' => __('pc.mini'),        'c' => '#F7941E', 'bg' => '#FCEBD6'],
        ];

        return Clinic::whereIn('id', array_keys($counts))->orderBy('name')->get()->map(function ($c) use ($counts, $typeMeta) {
            $m = $typeMeta[$c->type->value] ?? $typeMeta['fixed'];
            return [
                'name'  => $c->name,
                'em'    => $c->emirate->label(),
                'today' => $counts[$c->id] ?? 0,
                'tLabel'=> $m['label'], 'tC' => $m['c'], 'tBg' => $m['bg'],
            ];
        })->all();
    }
}
