<?php

namespace App\Http\Controllers;

use App\Models\PatientHistoryRecord;
use App\Support\DemoData;
use App\Support\RecordPresenter;
use Illuminate\View\View;

/**
 * Renders the hub, public and (non-functional) staff screens ported from the
 * design prototype. Clinic Management (super/clinics) is handled by ClinicController.
 */
class PageController extends Controller
{
    public function hub(): View
    {
        return view('hub');
    }

    // ---- Public ----
    public function booking(): View
    {
        return view('public.booking');
    }

    public function patient(): View
    {
        return view('public.patient');
    }

    // ---- Nurse ----
    public function nurseQueue(): View
    {
        $records = PatientHistoryRecord::query()
            ->with('patient', 'doctor', 'examination')
            ->whereIn('clinic_id', auth()->user()->clinicIds())
            ->latest()
            ->get();

        return view('staff.nurse.queue', $this->staff('nurse', 'nurse/queue', [
            'queue' => RecordPresenter::rows($records),
        ]));
    }

    public function nursePatients(): View
    {
        $records = PatientHistoryRecord::query()
            ->with('patient', 'doctor', 'examination')
            ->where('nurse_id', auth()->id())
            ->latest()
            ->get();

        return view('staff.nurse.patients', $this->staff('nurse', 'nurse/patients', [
            'queue' => RecordPresenter::rows($records),
        ]));
    }

    // ---- Doctor ----
    public function doctorAssigned(): View
    {
        $records = PatientHistoryRecord::query()
            ->with('patient', 'examination')
            ->where('assigned_doctor_id', auth()->id())
            ->whereIn('status', ['assigned', 'in_review'])
            ->latest()->get();

        return view('staff.doctor.assigned', $this->staff('doctor', 'doctor/assigned', [
            'doctorList' => RecordPresenter::rows($records, 'doctor.exam'),
        ]));
    }

    public function doctorCompleted(): View
    {
        // Cases this doctor has finished their exam on — keyed off the submitted examination,
        // so they stay visible after the admin routes the case onward (returned/assigned/completed).
        $records = PatientHistoryRecord::query()
            ->with('patient', 'examination')
            ->where('assigned_doctor_id', auth()->id())
            ->whereHas('examination', fn ($q) => $q->where('status', 'submitted'))
            ->latest()->get();

        return view('staff.doctor.completed', $this->staff('doctor', 'doctor/completed', [
            'completedList' => RecordPresenter::rows($records, 'doctor.exam'),
        ]));
    }

    // ---- Clinic administrator ----
    public function clinicQueue(): View
    {
        $clinicIds = auth()->user()->clinicIds();
        $records = PatientHistoryRecord::query()
            ->with('patient', 'doctor', 'examination')
            ->whereIn('clinic_id', $clinicIds)
            ->latest()->get();

        $base = PatientHistoryRecord::whereIn('clinic_id', $clinicIds);
        $miniStats = [
            ['label' => __('pc.today_col'),       'val' => (string) (clone $base)->whereDate('created_at', today())->count(), 'color' => '#E6017E'],
            ['label' => __('pc.unassigned'),      'val' => (string) (clone $base)->where('status', 'submitted')->count(), 'color' => '#2A6FDB'],
            ['label' => __('pc.in_progress'),     'val' => (string) (clone $base)->whereIn('status', ['assigned', 'in_review'])->count(), 'color' => '#7E4CC4'],
            ['label' => __('pc.returned'),        'val' => (string) (clone $base)->where('status', 'returned')->count(), 'color' => '#F7941E'],
            ['label' => __('pc.completed'),       'val' => (string) (clone $base)->whereIn('status', ['completed', 'report_sent'])->count(), 'color' => '#2E7D32'],
        ];

        return view('staff.clinic.queue', $this->staff('clinic', 'clinic/queue', [
            'miniStats' => $miniStats,
            'queue'     => RecordPresenter::rows($records, 'nurse.record.edit'),
        ]));
    }

    public function clinicRegister(): View
    {
        $clinicIds = auth()->user()->clinicIds();
        $nurses = \App\Models\User::role('nurse')
            ->whereHas('clinics', fn ($q) => $q->whereIn('clinics.id', $clinicIds))
            ->orderBy('name')->get();

        return view('staff.clinic.register', $this->staff('clinic', 'clinic/register', [
            'nurses'       => $nurses,
            'eidReaderUrl' => config('services.emirates_id.reader_url') ?: route('tools.eid.read'),
        ]));
    }

    public function clinicAssign(): View
    {
        $clinicIds = auth()->user()->clinicIds();

        // Every active case in the admin's clinics — awaiting first assignment, in progress,
        // or returned by a role for a decision. Completed cases drop off the board.
        $records = PatientHistoryRecord::query()
            ->with('patient', 'clinic', 'doctor', 'mammographer')
            ->whereIn('clinic_id', $clinicIds)
            ->whereIn('status', ['submitted', 'assigned', 'in_review', 'returned'])
            ->latest()->get();

        // Doctors and mammographers available per clinic the admin manages.
        $doctors = \App\Models\User::role('doctor')->with('clinics')
            ->whereHas('clinics', fn ($q) => $q->whereIn('clinics.id', $clinicIds))
            ->orderBy('name')->get();
        $mammographers = \App\Models\User::role('mammographer')->with('clinics')
            ->whereHas('clinics', fn ($q) => $q->whereIn('clinics.id', $clinicIds))
            ->orderBy('name')->get();

        return view('staff.clinic.assign', $this->staff('clinic', 'clinic/assign', [
            'assignList'    => RecordPresenter::rows($records, 'nurse.record.edit'),
            'records'       => $records,
            'doctors'       => $doctors,
            'mammographers' => $mammographers,
        ]));
    }

    public function clinicReports(): View
    {
        $clinicIds = auth()->user()->clinicIds();
        $s = \App\Support\DashboardService::stats([], $clinicIds);

        $reportStats = [
            ['label' => __('pc.total_records'), 'val' => number_format($s['total']),     'color' => '#E6017E', 'sub' => ''],
            ['label' => __('pc.abnormal_rate'), 'val' => $s['abnormalRate'].'%',          'color' => '#C62828', 'sub' => $s['abnormal'].' '.mb_strtolower(__('pc.abnormal'))],
            ['label' => __('pc.referrals'),     'val' => number_format($s['referrals']),  'color' => '#2A6FDB', 'sub' => $s['referralsClosed'].' '.mb_strtolower(__('pc.completed'))],
            ['label' => __('pc.screened_women'),'val' => number_format($s['completed']),  'color' => '#16A6A6', 'sub' => ''],
        ];

        // Real weekly throughput (last 6 days).
        $vals = [];
        $days = [];
        foreach (range(5, 0) as $d) {
            $day = today()->subDays($d);
            $vals[] = PatientHistoryRecord::whereIn('clinic_id', $clinicIds)->whereDate('created_at', $day)->count();
            $days[] = $day->isoFormat('ddd');
        }
        $max = max(array_merge([1], $vals));
        $weekBars = collect($vals)->map(fn ($v, $i) => ['val' => $v, 'day' => $days[$i], 'h' => round($v / $max * 100).'%'])->all();

        return view('staff.clinic.reports', $this->staff('clinic', 'clinic/reports', [
            'reportStats' => $reportStats,
            'weekBars'    => $weekBars,
        ]));
    }

    // ---- Super administrator ----
    public function superDashboard(\Illuminate\Http\Request $request): View
    {
        $filters = $request->only(['emirate', 'clinic_id', 'type', 'doctor_id', 'from', 'to']);
        $s = \App\Support\DashboardService::stats($filters);

        $dashStats = [
            ['icon' => '📋', 'tint' => '#FCE7F0', 'color' => '#E6017E', 'val' => number_format($s['total']),      'label' => __('pc.total_records'),  'sub' => '', 'subColor' => '#9A8F97'],
            ['icon' => '💗', 'tint' => '#E4F4EF', 'color' => '#16A6A6', 'val' => number_format($s['completed']),  'label' => __('pc.screened_women'), 'sub' => '', 'subColor' => '#2E7D32'],
            ['icon' => '⚠️', 'tint' => '#FBE4E4', 'color' => '#C62828', 'val' => $s['abnormalRate'].'%',          'label' => __('pc.abnormal_rate'),  'sub' => $s['abnormal'].' '.mb_strtolower(__('pc.abnormal')), 'subColor' => '#9A8F97'],
            ['icon' => '🔀', 'tint' => '#E3ECFB', 'color' => '#2A6FDB', 'val' => number_format($s['referrals']),  'label' => __('pc.referrals'),      'sub' => $s['referralsClosed'].' '.mb_strtolower(__('pc.completed')), 'subColor' => '#9A8F97'],
            ['icon' => '⏳', 'tint' => '#EEE6FA', 'color' => '#7E4CC4', 'val' => number_format($s['pending']),    'label' => __('pc.pending_cases'),  'sub' => __('pc.in_follow_up'), 'subColor' => '#9A8F97'],
        ];

        return view('super.dashboard', $this->staff('super', 'super/dashboard', [
            'dashStats'  => $dashStats,
            'byEmirate'  => $s['byEmirate'],
            'clinics'    => $s['byClinic'],
            'stats'      => $s,
            'filters'    => $filters,
            'emirates'   => \App\Enums\Emirate::options(),
            'types'      => \App\Enums\ClinicType::options(),
        ]));
    }

    public function superAudit(): View
    {
        $entries = \App\Models\AuditLog::with('user')->latest('id')->limit(100)->get()->map(function ($a) {
            $icons = ['patient.registered' => '🧾', 'record.submitted' => '📝', 'record.assigned' => '🔀', 'record.completed' => '✅', 'exam.submitted' => '🔬', 'report.sent' => '📨', 'report.downloaded' => '📤', 'booking.created' => '📅', 'patient.otp_verified' => '🔓'];
            return [
                'who'  => $a->actor_name,
                'act'  => $a->description ?? $a->action,
                'ent'  => $a->action,
                't'    => $a->created_at?->diffForHumans(),
                'ic'   => $icons[$a->action] ?? '•',
                'tint' => '#FCEFF5',
            ];
        })->all();

        return view('super.audit', $this->staff('super', 'super/audit', ['audit' => $entries]));
    }

    /** Shared props for staff screens (sidebar reflects the acting user + active route). */
    private function staff(string $role, string $route, array $extra = []): array
    {
        return array_merge([
            'sidebarRole' => auth()->user()?->sidebarRole() ?? $role,
            'route'       => $route,
        ], $extra);
    }
}
