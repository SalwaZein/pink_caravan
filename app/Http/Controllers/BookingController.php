<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** Public — persist a service booking (Booking + one service line item). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_type'   => ['required', 'in:A,B,C,D'],
            'organisation'   => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'mobile'         => ['required', 'string', 'max:40'],
            'emirate'        => ['nullable', 'string', 'max:255'],
            'estimated_participants' => ['nullable', 'integer', 'min:0'],
            'notes'          => ['nullable', 'string'],
            'event_date'     => ['nullable', 'date'],
            'venue'          => ['nullable', 'string', 'max:255'],
        ]);

        $year = (int) now()->format('Y');

        $booking = DB::transaction(function () use ($data, $year) {
            $booking = Booking::create([
                'ref_no'         => Booking::nextRef($year),
                'organisation'   => $data['organisation'],
                'contact_person' => $data['contact_person'],
                'email'          => $data['email'],
                'mobile'         => $data['mobile'],
                'emirate'        => $data['emirate'] ?? null,
                'estimated_participants' => $data['estimated_participants'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);

            $booking->services()->create([
                'service_type' => $data['service_type'],
                'event_date'   => $data['event_date'] ?? null,
                'venue'        => $data['venue'] ?? null,
            ]);

            return $booking;
        });

        Audit::log('booking.created', $booking, "{$booking->ref_no} — {$booking->organisation}");

        return redirect()->route('booking')->with('booking_ref', $booking->ref_no);
    }

    /** Super admin — booking queue. */
    public function adminIndex(): View
    {
        $bookings = Booking::with('services')->latest()->get();

        return view('super.bookings', [
            'bookings'    => $bookings,
            'sidebarRole' => auth()->user()->sidebarRole(),
            'route'       => 'super/bookings',
        ]);
    }
}
