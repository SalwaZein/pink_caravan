@extends('layouts.app')
@section('title', 'Pink Caravan — '.$booking->ref_no)

@php
    $isRtl = app()->getLocale() === 'ar';
    [$sc, $sbg] = $booking->statusColors();
    $fmt = fn ($dt) => $dt ? $dt->format('d M Y · H:i') : null;
    $done = fn ($who, $at) => $who && $at
        ? __('pc.booking_by_on', ['name' => $who->name, 'time' => $fmt($at)])
        : null;

    $card  = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $label = 'font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#9A8F97;';
    $field = 'width:100%;padding:10px 12px;border:1px solid #E7D6E1;border-radius:10px;font-size:14px;background:#FCFAFB;';
    $btn   = 'cursor:pointer;border:0;color:#fff;font-weight:700;font-size:13.5px;padding:11px 18px;border-radius:11px;';
    $pink  = 'background:linear-gradient(90deg,#E6017E,#C0116E);box-shadow:0 5px 15px rgba(230,1,126,.2);';
    $done_step = 'display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:10px;';
    $wait_step = 'font-size:12.5px;color:#B08AA0;font-style:italic;';
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <a href="{{ route('super.bookings.index') }}" style="display:inline-block;margin-bottom:16px;font-size:13px;font-weight:600;color:#8A6B7C;text-decoration:none;">← {{ __('pc.booking_back') }}</a>

    @if (session('status'))
        <div class="pc-anim" style="margin-bottom:16px;display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:13px;font-weight:700;padding:10px 16px;border-radius:12px;">
            <span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom:16px;background:#FBE9E7;color:#C0392B;font-size:13px;font-weight:600;padding:10px 16px;border-radius:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1.15fr .85fr;gap:20px;align-items:start;">
        {{-- ============ LEFT: booking details ============ --}}
        <div class="pc-anim" style="{{ $card }}overflow:hidden;">
            <div style="padding:20px 22px;border-bottom:1px solid #F3E7EE;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div style="font-size:12px;color:#9A8F97;font-weight:600;">{{ $booking->ref_no }}</div>
                    <div style="font-size:19px;font-weight:700;">{{ $booking->organisation }}</div>
                </div>
                <span style="font-size:12px;font-weight:700;padding:5px 13px;border-radius:999px;color:{{ $sc }};background:{{ $sbg }};white-space:nowrap;">{{ $booking->statusLabel() }}</span>
            </div>

            <div style="padding:20px 22px;display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                <div><div style="{{ $label }}">{{ __('pc.booking_contact') }}</div><div style="font-size:14px;margin-top:4px;">{{ $booking->contact_person }}</div></div>
                <div><div style="{{ $label }}">{{ __('pc.emirate') }}</div><div style="font-size:14px;margin-top:4px;">{{ $booking->emirate ? __('pc.em_'.$booking->emirate) : '—' }}</div></div>
                <div><div style="{{ $label }}">{{ __('pc.email') }}</div><div style="font-size:14px;margin-top:4px;word-break:break-all;">{{ $booking->email }}</div></div>
                <div><div style="{{ $label }}">{{ __('pc.mobile') }}</div><div style="font-size:14px;margin-top:4px;direction:ltr;text-align:{{ $isRtl ? 'end' : 'start' }};">{{ $booking->mobile }}</div></div>
                <div><div style="{{ $label }}">{{ __('pc.booking_participants') }}</div><div style="font-size:14px;margin-top:4px;">{{ $booking->estimated_participants ?? '—' }}</div></div>
                <div><div style="{{ $label }}">{{ __('pc.booking_submitted_on') }}</div><div style="font-size:14px;margin-top:4px;">{{ $fmt($booking->created_at) }}</div></div>
                @if ($booking->notes)
                    <div style="grid-column:1 / -1;"><div style="{{ $label }}">{{ __('pc.booking_notes') }}</div><div style="font-size:14px;margin-top:4px;line-height:1.5;color:#5B4A54;">{{ $booking->notes }}</div></div>
                @endif
            </div>

            <div style="padding:4px 22px 22px;">
                <div style="{{ $label }}margin-bottom:8px;">{{ __('pc.services') }}</div>
                @foreach ($booking->services as $s)
                    <div style="display:flex;justify-content:space-between;gap:12px;padding:11px 14px;border:1px solid #F1E4EC;border-radius:11px;margin-bottom:8px;">
                        <div style="font-weight:600;font-size:13.5px;">{{ __('pc.'.($svcTitles[$s->service_type] ?? 'services')) }}</div>
                        <div style="font-size:12.5px;color:#9A8F97;text-align:end;">
                            {{ $s->event_date ? $s->event_date->format('d M Y') : '—' }}@if ($s->venue) · {{ $s->venue }}@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ RIGHT: workflow ============ --}}
        <div class="pc-anim" style="{{ $card }}overflow:hidden;">
            <div style="padding:18px 22px;border-bottom:1px solid #F3E7EE;font-size:15px;font-weight:700;">{{ __('pc.booking_workflow') }}</div>
            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:22px;">

                {{-- STEP 1 · Review & approve --}}
                <div>
                    <div style="{{ $label }}margin-bottom:10px;">{{ __('pc.booking_step_review') }}</div>
                    @if ($booking->status === \App\Models\Booking::STATUS_REJECTED)
                        <div style="background:#FBE9E7;color:#C0392B;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:10px;">✕ {{ __('pc.booking_status_rejected') }} — {{ $done($booking->reviewedBy, $booking->rejected_at) }}</div>
                        @if ($booking->rejection_reason)
                            <div style="font-size:12.5px;color:#8A6B7C;margin-top:8px;line-height:1.5;">“{{ $booking->rejection_reason }}”</div>
                        @endif
                    @elseif ($booking->status === \App\Models\Booking::STATUS_NEW)
                        @can('approve_bookings')
                            <div x-data="{ reject: false }" style="display:flex;flex-direction:column;gap:10px;">
                                <div style="display:flex;gap:10px;">
                                    <form method="POST" action="{{ route('super.bookings.approve', $booking) }}">
                                        @csrf
                                        <button type="submit" style="{{ $btn }}{{ $pink }}">✓ {{ __('pc.booking_approve') }}</button>
                                    </form>
                                    <button type="button" x-on:click="reject = !reject" style="cursor:pointer;background:#FCFAFB;border:1px solid #E7D6E1;color:#C0392B;font-weight:700;font-size:13.5px;padding:11px 18px;border-radius:11px;">{{ __('pc.booking_reject') }}</button>
                                </div>
                                <form method="POST" action="{{ route('super.bookings.reject', $booking) }}" x-show="reject" x-cloak style="display:flex;flex-direction:column;gap:8px;">
                                    @csrf
                                    <label style="{{ $label }}">{{ __('pc.booking_reject_reason') }}</label>
                                    <textarea name="rejection_reason" rows="2" required style="{{ $field }}"></textarea>
                                    <button type="submit" style="{{ $btn }}background:#C0392B;align-self:flex-start;">{{ __('pc.booking_reject') }}</button>
                                </form>
                            </div>
                        @else
                            <div style="{{ $wait_step }}">{{ __('pc.booking_no_permission') }}</div>
                        @endcan
                    @else
                        <div style="{{ $done_step }}">✓ {{ __('pc.booking_status_approved') }} — {{ $done($booking->approvedBy, $booking->approved_at) }}</div>
                    @endif
                </div>

                {{-- STEP 2 · Payment --}}
                <div>
                    <div style="{{ $label }}margin-bottom:10px;">{{ __('pc.booking_step_payment') }}</div>
                    @if (in_array($booking->status, [\App\Models\Booking::STATUS_PAID, \App\Models\Booking::STATUS_COMPLETED], true))
                        <div style="{{ $done_step }}">✓ {{ __('pc.booking_status_paid') }} — {{ $done($booking->paidBy, $booking->paid_at) }}</div>
                        @if ($booking->payment_amount || $booking->payment_reference)
                            <div style="font-size:12.5px;color:#8A6B7C;margin-top:8px;">
                                @if ($booking->payment_amount) AED {{ number_format((float) $booking->payment_amount, 2) }} @endif
                                @if ($booking->payment_reference) · {{ $booking->payment_reference }} @endif
                            </div>
                        @endif
                    @elseif ($booking->status === \App\Models\Booking::STATUS_APPROVED)
                        @can('mark_bookings_paid')
                            <form method="POST" action="{{ route('super.bookings.paid', $booking) }}" style="display:flex;flex-direction:column;gap:10px;">
                                @csrf
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div><label style="{{ $label }}">{{ __('pc.booking_payment_amount') }}</label><input type="number" step="0.01" min="0" name="payment_amount" style="{{ $field }}margin-top:4px;"></div>
                                    <div><label style="{{ $label }}">{{ __('pc.booking_payment_reference') }}</label><input type="text" name="payment_reference" style="{{ $field }}margin-top:4px;"></div>
                                </div>
                                <button type="submit" style="{{ $btn }}{{ $pink }}align-self:flex-start;">💳 {{ __('pc.booking_mark_paid') }}</button>
                            </form>
                        @else
                            <div style="{{ $wait_step }}">{{ __('pc.booking_no_permission') }}</div>
                        @endcan
                    @else
                        <div style="{{ $wait_step }}">{{ __('pc.booking_awaiting_prev') }}</div>
                    @endif
                </div>

                {{-- STEP 3 · Completion --}}
                <div>
                    <div style="{{ $label }}margin-bottom:10px;">{{ __('pc.booking_step_complete') }}</div>
                    @if ($booking->status === \App\Models\Booking::STATUS_COMPLETED)
                        <div style="{{ $done_step }}">✓ {{ __('pc.booking_status_completed') }} — {{ $done($booking->completedBy, $booking->completed_at) }}</div>
                        @if ($booking->completion_notes)
                            <div style="font-size:12.5px;color:#8A6B7C;margin-top:8px;line-height:1.5;">{{ $booking->completion_notes }}</div>
                        @endif
                        @if ($booking->completion_report_path)
                            <a href="{{ route('super.bookings.report', $booking) }}" target="_blank" style="display:inline-block;margin-top:10px;font-size:13px;font-weight:700;color:#E6017E;text-decoration:none;">📄 {{ __('pc.booking_view_report') }}</a>
                        @endif
                    @elseif ($booking->status === \App\Models\Booking::STATUS_PAID)
                        @can('complete_bookings')
                            <form method="POST" action="{{ route('super.bookings.complete', $booking) }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:10px;">
                                @csrf
                                <div><label style="{{ $label }}">{{ __('pc.booking_completion_report') }}</label><input type="file" name="completion_report" accept="application/pdf" required style="{{ $field }}margin-top:4px;padding:9px;"></div>
                                <div><label style="{{ $label }}">{{ __('pc.booking_completion_notes') }}</label><textarea name="completion_notes" rows="2" style="{{ $field }}margin-top:4px;"></textarea></div>
                                <button type="submit" style="{{ $btn }}{{ $pink }}align-self:flex-start;">✅ {{ __('pc.booking_mark_completed') }}</button>
                            </form>
                        @else
                            <div style="{{ $wait_step }}">{{ __('pc.booking_no_permission') }}</div>
                        @endcan
                    @else
                        <div style="{{ $wait_step }}">{{ __('pc.booking_awaiting_prev') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-staff-shell>
@endsection
