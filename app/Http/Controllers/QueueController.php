<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\QueueToken;
use App\Support\Audit;
use App\Support\QueueNotifier;
use App\Support\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * WhatsApp token & queue management (business feedback #7).
 *
 * A volunteer at the clinic door registers each arriving visitor by name and
 * WhatsApp number; the system issues the next token for that clinic (the
 * location) that day and messages it to her. The desk then works the board:
 *   call → the visitor is told to proceed;
 *   entered → she walked in, and the next visitor is automatically warned that
 *             her turn is approaching;
 *   served / no-show / cancel → close the token.
 *
 * The clinic admin and super admin hold the same manage_queue permission, so
 * they can watch and manage any of their clinics' queues from the backend.
 */
class QueueController extends Controller
{
    /** The queue board for one clinic (location) on one day. */
    public function index(Request $request): View
    {
        $clinics = $this->availableClinics();

        abort_if($clinics->isEmpty(), 403, 'No clinic is assigned to this account.');

        $clinic = $this->resolveClinic($request, $clinics);
        $date   = $this->resolveDate($request);

        $tokens = QueueToken::forDesk($clinic->id, $date)
            ->with('issuer')
            ->orderByRaw(self::BOARD_ORDER)
            ->orderBy('number')
            ->get();

        return view('staff.volunteer.queue', [
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'queue',
            'clinics'     => $clinics,
            'clinic'      => $clinic,
            'date'        => $date,
            'tokens'      => $tokens,
            'stats'       => QueueService::stats($clinic->id, $date),
            'nowServing'  => $tokens->firstWhere('status', QueueToken::IN_CLINIC)
                             ?? $tokens->firstWhere('status', QueueToken::CALLED),
        ]);
    }

    /** Register an arriving visitor → issue a token and WhatsApp it to her. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_id'       => ['required', 'integer'],
            'patient_name'    => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:40'],
        ]);

        $clinic = $this->authorizedClinic((int) $data['clinic_id']);

        $token = QueueService::issue(
            $clinic,
            $data['patient_name'],
            $data['whatsapp_number'],
            auth()->id(),
        );

        $status = QueueNotifier::issued($token);

        Audit::log('queue.token_issued', $token, "{$token->code} — {$token->patient_name} (WhatsApp: {$status})");

        return $this->backToBoard($clinic, $token->queue_date)
            ->with('status', __('pc.qt_issued_ok', ['code' => $token->code, 'channel' => $status]));
    }

    /** Call this token in — the visitor is told to proceed to the clinic. */
    public function call(QueueToken $token): RedirectResponse
    {
        $this->authorizedClinic($token->clinic_id);
        abort_unless($token->isOpen(), 422);

        $token->update([
            'status'    => QueueToken::CALLED,
            'called_at' => now(),
            'called_by' => auth()->id(),
        ]);

        $status = QueueNotifier::called($token);
        Audit::log('queue.token_called', $token, "{$token->code} (WhatsApp: {$status})");

        return $this->backToBoard($token->clinic, $token->queue_date)
            ->with('status', __('pc.qt_called_ok', ['code' => $token->code, 'channel' => $status]));
    }

    /**
     * The visitor has entered the clinic. This is the trigger the business asked
     * for: the moment one person walks in, the next one still waiting gets the
     * "your turn is approaching" WhatsApp automatically.
     */
    public function enter(QueueToken $token): RedirectResponse
    {
        $this->authorizedClinic($token->clinic_id);
        abort_unless($token->isOpen(), 422);

        $token->update([
            'status'     => QueueToken::IN_CLINIC,
            'entered_at' => now(),
            'called_at'  => $token->called_at ?? now(),
        ]);

        Audit::log('queue.token_entered', $token, $token->code);

        $message = __('pc.qt_entered_ok', ['code' => $token->code]);

        if ($next = QueueService::nextUpcoming($token)) {
            $status = QueueNotifier::approaching($next);
            $next->update(['status' => QueueToken::NOTIFIED, 'notified_at' => now()]);

            Audit::log('queue.token_notified', $next, "{$next->code} (WhatsApp: {$status})");
            $message .= ' '.__('pc.qt_next_notified', ['code' => $next->code, 'channel' => $status]);
        }

        return $this->backToBoard($token->clinic, $token->queue_date)->with('status', $message);
    }

    /** Close a token: served, no-show or cancelled. */
    public function close(Request $request, QueueToken $token): RedirectResponse
    {
        $this->authorizedClinic($token->clinic_id);

        $outcome = $request->validate([
            'outcome' => ['required', 'in:'.implode(',', [QueueToken::SERVED, QueueToken::NO_SHOW, QueueToken::CANCELLED])],
        ])['outcome'];

        $token->update([
            'status'    => $outcome,
            'served_at' => $outcome === QueueToken::SERVED ? now() : $token->served_at,
            'closed_at' => now(),
        ]);

        Audit::log('queue.token_'.$outcome, $token, $token->code);

        return $this->backToBoard($token->clinic, $token->queue_date)
            ->with('status', __('pc.qt_closed_ok', ['code' => $token->code, 'outcome' => __('pc.qt_'.$outcome)]));
    }

    /** Re-send the token message (a visitor who says nothing arrived). */
    public function resend(QueueToken $token): RedirectResponse
    {
        $this->authorizedClinic($token->clinic_id);

        $status = QueueNotifier::issued($token);
        Audit::log('queue.token_resent', $token, "{$token->code} (WhatsApp: {$status})");

        return $this->backToBoard($token->clinic, $token->queue_date)
            ->with('status', __('pc.qt_resent_ok', ['code' => $token->code, 'channel' => $status]));
    }

    // ---- helpers ----

    /** Open tokens sort to the top of the board; closed ones sink. */
    private const BOARD_ORDER = "CASE status
        WHEN 'in_clinic' THEN 0
        WHEN 'called' THEN 1
        WHEN 'notified' THEN 2
        WHEN 'waiting' THEN 3
        ELSE 4 END";

    /** Clinics this user may run a queue for (all of them for a clinic-less admin). */
    private function availableClinics()
    {
        $ids = auth()->user()->clinicIds();

        return empty($ids)
            ? Clinic::where('is_active', true)->orderBy('name')->get()
            : Clinic::whereIn('id', $ids)->orderBy('name')->get();
    }

    private function resolveClinic(Request $request, $clinics): Clinic
    {
        $requested = (int) $request->query('clinic', 0);

        return $clinics->firstWhere('id', $requested) ?? $clinics->first();
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = (string) $request->query('date', '');

        try {
            return $raw ? Carbon::parse($raw)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /** The clinic must be one this user is allowed to work a queue for. */
    private function authorizedClinic(int $clinicId): Clinic
    {
        $clinic = $this->availableClinics()->firstWhere('id', $clinicId);

        abort_unless($clinic !== null, 403);

        return $clinic;
    }

    private function backToBoard(Clinic $clinic, Carbon|string $date): RedirectResponse
    {
        return redirect()->route('queue.index', [
            'clinic' => $clinic->id,
            'date'   => $date instanceof Carbon ? $date->toDateString() : $date,
        ]);
    }
}
