<?php

namespace App\Http\Controllers;

use App\Enums\Emirate;
use App\Models\Booking;
use App\Support\Audit;
use App\Support\BookingDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Administration → Service Bookings review workflow.
 *
 * A service booking submitted from the public site moves through the lifecycle
 * new → approved → paid → completed (or new → rejected). Each transition is a
 * distinct, permission-gated action, recorded with actor + timestamp and audited.
 */
class ServiceBookingController extends Controller
{
    private const SVC_TITLES = ['A' => 'svc_a_title', 'B' => 'svc_b_title', 'C' => 'svc_c_title', 'D' => 'svc_d_title'];

    /** Bookings dashboard — pipeline metrics for service bookings (review_bookings). */
    public function dashboard(Request $request): View
    {
        $filters = $request->only(['status', 'service_type', 'emirate', 'from', 'to']);

        return view('super.booking-dashboard', [
            'stats'        => BookingDashboardService::stats($filters),
            'filters'      => $filters,
            'emirates'     => Emirate::options(),
            'serviceTypes' => [
                'A' => __('pc.svc_a_title'), 'B' => __('pc.svc_b_title'),
                'C' => __('pc.svc_c_title'), 'D' => __('pc.svc_d_title'),
            ],
            'statuses'     => collect(Booking::statuses())
                ->mapWithKeys(fn ($s) => [$s => __('pc.booking_status_'.$s)])->all(),
            'sidebarRole'  => auth()->user()->sidebarRole(),
            'route'        => 'super/bookings/dashboard',
        ]);
    }

    /** List every service booking (review_bookings). */
    public function index(): View
    {
        return view('super.bookings', [
            'bookings'    => Booking::with('services')->latest()->get(),
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/bookings',
        ]);
    }

    /** Review a single booking and act on it (review_bookings). */
    public function show(Booking $booking): View
    {
        $booking->load('services', 'reviewedBy', 'approvedBy', 'paidBy', 'completedBy');

        return view('super.booking-show', [
            'booking'     => $booking,
            'svcTitles'   => self::SVC_TITLES,
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/bookings',
        ]);
    }

    /** Approve a new request (approve_bookings). */
    public function approve(Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === Booking::STATUS_NEW, 422);

        $booking->update([
            'status'      => Booking::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Audit::log('booking.approved', $booking, $booking->ref_no);

        return back()->with('status', __('pc.booking_approved_ok', ['ref' => $booking->ref_no]));
    }

    /** Reject a new request (approve_bookings — the review decision). */
    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === Booking::STATUS_NEW, 422);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $booking->update([
            'status'           => Booking::STATUS_REJECTED,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
            'rejected_at'      => now(),
        ]);

        Audit::log('booking.rejected', $booking, $booking->ref_no);

        return back()->with('status', __('pc.booking_rejected_ok', ['ref' => $booking->ref_no]));
    }

    /** Mark an approved booking as paid (mark_bookings_paid). */
    public function markPaid(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === Booking::STATUS_APPROVED, 422);

        $data = $request->validate([
            'payment_amount'    => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $booking->update([
            'status'            => Booking::STATUS_PAID,
            'paid_by'           => auth()->id(),
            'paid_at'           => now(),
            'payment_amount'    => $data['payment_amount'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        Audit::log('booking.paid', $booking, $booking->ref_no);

        return back()->with('status', __('pc.booking_paid_ok', ['ref' => $booking->ref_no]));
    }

    /** Mark a paid booking as completed and attach the completion report (complete_bookings). */
    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === Booking::STATUS_PAID, 422);

        $data = $request->validate([
            'completion_report' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'completion_notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->file('completion_report')
            ->storeAs('service-bookings', $booking->ref_no.'-'.now()->format('YmdHis').'.pdf');

        $booking->update([
            'status'                 => Booking::STATUS_COMPLETED,
            'completed_by'           => auth()->id(),
            'completed_at'           => now(),
            'completion_report_path' => $path,
            'completion_notes'       => $data['completion_notes'] ?? null,
        ]);

        Audit::log('booking.completed', $booking, $booking->ref_no);

        return back()->with('status', __('pc.booking_completed_ok', ['ref' => $booking->ref_no]));
    }

    /** Stream the attached completion report PDF (review_bookings). */
    public function report(Booking $booking)
    {
        abort_unless(
            $booking->completion_report_path && Storage::exists($booking->completion_report_path),
            404,
        );

        return response(Storage::get($booking->completion_report_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="completion-'.$booking->ref_no.'.pdf"',
        ]);
    }
}
