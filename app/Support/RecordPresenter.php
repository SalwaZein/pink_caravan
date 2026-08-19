<?php

namespace App\Support;

use App\Models\PatientHistoryRecord;

/**
 * Turns a real PatientHistoryRecord into the row shape the staff queue/list
 * Blade views expect (mirrors the demo-data keys so the views are reused).
 */
class RecordPresenter
{
    private const TINTS = ['#16A6A6', '#2A6FDB', '#7E4CC4', '#F7941E', '#E6017E', '#43A047'];

    public static function statusMeta(): array
    {
        return [
            'draft'     => ['label' => __('pc.draft'),           'c' => '#6B4257', 'bg' => '#F1E7ED'],
            'submitted' => ['label' => __('pc.unassigned'),      'c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            'assigned'  => ['label' => __('pc.assigned'),        'c' => '#2A6FDB', 'bg' => '#E3ECFB'],
            'in_review' => ['label' => __('pc.in_review'),       'c' => '#7E4CC4', 'bg' => '#EEE6FA'],
            'returned'  => ['label' => __('pc.returned'),        'c' => '#B25E00', 'bg' => '#FBEEDD'],
            'completed' => ['label' => __('pc.completed'),       'c' => '#2E7D32', 'bg' => '#E4F4EF'],
            'report_sent' => ['label' => __('pc.status_report_sent'), 'c' => '#0E7C6B', 'bg' => '#DFF3EF'],
        ];
    }

    public static function resultMeta(): array
    {
        return [
            'normal'   => ['label' => __('pc.normal'),   'c' => '#2E7D32', 'bg' => '#E4F4EF'],
            'abnormal' => ['label' => __('pc.abnormal'), 'c' => '#C62828', 'bg' => '#FBE4E4'],
            null       => ['label' => '—',               'c' => '#B7A9B2', 'bg' => '#F4EEF1'],
        ];
    }

    public static function row(PatientHistoryRecord $r, int $i, string $openRoute = 'nurse.record.edit'): array
    {
        $status = self::statusMeta()[$r->status] ?? self::statusMeta()['draft'];
        $result = self::resultMeta()[$r->finalResult()] ?? self::resultMeta()[null];

        $name = $r->patient?->full_name ?? '—';
        $init = collect(explode(' ', $name))->map(fn ($w) => mb_substr($w, 0, 1))->join('');

        return [
            'id'       => $r->id,
            'ref'      => $r->ref_no,
            'name'     => $name,
            'init'     => mb_substr($init, 0, 2),
            'tint'     => self::TINTS[$i % count(self::TINTS)],
            'age'      => $r->patient?->dob?->age ?? '—',
            'emirate'  => $r->patient?->emirate ? __('pc.em_'.$r->patient->emirate) : '',
            'time'     => $r->created_at?->format('H:i') ?? '',
            'stLabel'  => $status['label'], 'stC' => $status['c'], 'stBg' => $status['bg'],
            'resLabel' => $result['label'], 'resC' => $result['c'], 'resBg' => $result['bg'],
            'doc'      => $r->doctor?->name ?? '—',
            'editable' => $r->status === PatientHistoryRecord::DRAFT,
            'openUrl'  => route($openRoute, $r),
        ];
    }

    /** @param iterable<PatientHistoryRecord> $records */
    public static function rows(iterable $records, string $openRoute = 'nurse.record.edit'): array
    {
        $out = [];
        $i = 0;
        foreach ($records as $r) {
            $out[] = self::row($r, $i++, $openRoute);
        }
        return $out;
    }
}
