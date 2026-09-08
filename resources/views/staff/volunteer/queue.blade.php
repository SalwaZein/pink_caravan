@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_queue_desk'))

@php
    use App\Models\QueueToken;

    $inp  = 'display:block;width:100%;margin-top:5px;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;';
    $lbl  = 'font-size:12.5px;font-weight:600;color:#6B4257;';
    $card = 'background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:22px 24px;margin-bottom:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);';
    $isToday = $date->isSameDay(now());

    $tile = function (string $label, $value, string $tint) {
        return '<div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:14px 16px;">'
            .'<div style="font-size:11px;color:#9A8F97;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">'.$label.'</div>'
            .'<div style="font-size:24px;font-weight:700;margin-top:3px;color:'.$tint.';">'.$value.'</div></div>';
    };
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    @if (session('status'))
        <div class="pc-anim" style="margin-bottom:14px;background:#E4F4EF;border:1px solid #BFE6DA;border-radius:12px;padding:13px 18px;color:#1E6F5C;font-size:13.5px;">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div style="margin-bottom:14px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13px;">
            <ul style="margin:0;padding-inline-start:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Location + day: the queue is numbered per clinic, per day. --}}
    <form method="GET" action="{{ route('queue.index') }}" class="pc-anim"
          style="{{ $card }}display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px;">
        <label style="{{ $lbl }}flex:1;min-width:220px;">📍 {{ __('pc.qt_location') }}
            <select name="clinic" style="{{ $inp }}" onchange="this.form.submit()">
                @foreach ($clinics as $c)
                    <option value="{{ $c->id }}" @selected($c->id === $clinic->id)>{{ $c->name }} ({{ $c->code }})</option>
                @endforeach
            </select>
        </label>
        <label style="{{ $lbl }}min-width:260px;">📅 {{ __('pc.qt_day') }}
            <x-date-field name="date" :value="$date->toDateString()" :min-year="now()->year - 1" :max-year="now()->year + 1" />
        </label>
        <button type="submit" style="cursor:pointer;background:#fff;color:#6B4257;font-weight:700;font-size:13.5px;padding:11px 20px;border:1px solid #E3D2DC;border-radius:11px;">{{ __('pc.qt_show') }}</button>
        @unless ($isToday)
            <a href="{{ route('queue.index', ['clinic' => $clinic->id]) }}" style="font-size:12.5px;font-weight:700;color:#E6017E;text-decoration:none;padding-bottom:12px;">{{ __('pc.qt_back_today') }} →</a>
        @endunless
    </form>

    <div class="pc-cols-4" style="gap:14px;margin-bottom:16px;">
        {!! $tile(__('pc.qt_stat_total'), $stats['total'], '#6B4257') !!}
        {!! $tile(__('pc.qt_stat_waiting'), $stats['waiting'], '#B25E00') !!}
        {!! $tile(__('pc.qt_stat_in_clinic'), $stats['called'], '#2A6FDB') !!}
        {!! $tile(__('pc.qt_stat_served'), $stats['served'], '#2E7D32') !!}
    </div>

    @if ($nowServing)
        <div class="pc-anim" style="{{ $card }}display:flex;align-items:center;gap:16px;background:linear-gradient(90deg,#FCEFF5,#fff);">
            <div style="font-size:11px;color:#9A8F97;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">{{ __('pc.qt_now_serving') }}</div>
            <div style="font-size:26px;font-weight:700;color:#E6017E;letter-spacing:-.01em;">{{ $nowServing->code }}</div>
            <div style="font-size:14px;font-weight:600;color:#6B4257;">{{ $nowServing->patient_name }}</div>
        </div>
    @endif

    {{-- 1. Register an arriving visitor → issue + WhatsApp the token. --}}
    @if ($isToday)
        <form method="POST" action="{{ route('queue.store') }}" class="pc-anim" style="{{ $card }}">
            @csrf
            <input type="hidden" name="clinic_id" value="{{ $clinic->id }}" />
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">➕</div>
                <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.qt_add_title') }}</h3>
            </div>
            <p style="font-size:12px;color:#9A8F97;margin:0 0 14px;">{{ __('pc.qt_add_hint') }}</p>
            <div style="display:grid;grid-template-columns:1.4fr 1fr auto;gap:14px;align-items:end;" class="pc-stack-sm">
                <label style="{{ $lbl }}">{{ __('pc.qt_visitor_name') }} *
                    <input name="patient_name" value="{{ old('patient_name') }}" required autocomplete="off" style="{{ $inp }}" />
                </label>
                <label style="{{ $lbl }}">{{ __('pc.qt_whatsapp') }} *
                    <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" required type="tel" inputmode="tel"
                           placeholder="{{ __('pc.qt_whatsapp_ph') }}" autocomplete="off" style="{{ $inp }}" />
                </label>
                <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;font-size:14px;padding:12px 24px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">🎟️ {{ __('pc.qt_issue') }}</button>
            </div>
        </form>
    @endif

    {{-- 2. The board. --}}
    <div class="pc-anim" style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#FAF4F7;">
            <div style="font-size:13.5px;font-weight:700;color:#6B4257;">{{ $clinic->name }} · {{ $date->format('d M Y') }}</div>
            <a href="{{ route('queue.index', ['clinic' => $clinic->id, 'date' => $date->toDateString()]) }}"
               style="font-size:12.5px;font-weight:700;color:#E6017E;text-decoration:none;">↻ {{ __('pc.qt_refresh') }}</a>
        </div>

        <div class="pc-tbl-wrap">
        <div class="pc-tbl">
            <div style="display:grid;grid-template-columns:120px 1.3fr 150px 130px 1fr;gap:12px;padding:12px 20px;background:#FDF8FA;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;border-top:1px solid #F3E7EE;">
                <div>{{ __('pc.qt_token') }}</div><div>{{ __('pc.qt_visitor') }}</div><div>{{ __('pc.qt_whatsapp') }}</div><div>{{ __('pc.status') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div>
            </div>

            @forelse ($tokens as $t)
                @php($col = QueueToken::colorsFor($t->status))
                <div style="display:grid;grid-template-columns:120px 1.3fr 150px 130px 1fr;gap:12px;align-items:center;padding:12px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;{{ $t->isOpen() ? '' : 'opacity:.62;' }}">
                    <div style="font-weight:700;color:#6B4257;">{{ $t->code }}</div>
                    <div>
                        <div style="font-weight:600;">{{ $t->patient_name }}</div>
                        <div style="font-size:11.5px;color:#9A8F97;margin-top:1px;">{{ __('pc.qt_arrived') }} {{ $t->created_at?->format('H:i') }}</div>
                    </div>
                    <div style="font-size:12.5px;color:#6B6472;direction:ltr;text-align:start;">{{ $t->whatsapp_number }}</div>
                    <div><span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $col['c'] }};background:{{ $col['bg'] }};">{{ $t->statusLabel() }}</span></div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                        @if ($t->isOpen())
                            @if (in_array($t->status, QueueToken::UPCOMING, true))
                                <form method="POST" action="{{ route('queue.call', $t) }}">@csrf
                                    <button type="submit" style="cursor:pointer;background:#E3ECFB;color:#2A6FDB;font-weight:700;font-size:12px;padding:7px 12px;border:none;border-radius:8px;">📣 {{ __('pc.qt_call') }}</button>
                                </form>
                            @endif
                            @if ($t->status !== QueueToken::IN_CLINIC)
                                {{-- Entering the clinic auto-warns the next visitor that her turn is approaching. --}}
                                <form method="POST" action="{{ route('queue.enter', $t) }}">@csrf
                                    <button type="submit" style="cursor:pointer;background:#EEE6FA;color:#7E4CC4;font-weight:700;font-size:12px;padding:7px 12px;border:none;border-radius:8px;">🚪 {{ __('pc.qt_entered') }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('queue.close', $t) }}">@csrf
                                <input type="hidden" name="outcome" value="{{ QueueToken::SERVED }}" />
                                <button type="submit" style="cursor:pointer;background:#E4F4EF;color:#2E7D32;font-weight:700;font-size:12px;padding:7px 12px;border:none;border-radius:8px;">✓ {{ __('pc.qt_mark_served') }}</button>
                            </form>
                            <form method="POST" action="{{ route('queue.close', $t) }}">@csrf
                                <input type="hidden" name="outcome" value="{{ QueueToken::NO_SHOW }}" />
                                <button type="submit" style="cursor:pointer;background:#FBE4E4;color:#C62828;font-weight:700;font-size:12px;padding:7px 12px;border:none;border-radius:8px;">✕ {{ __('pc.qt_mark_no_show') }}</button>
                            </form>
                            <form method="POST" action="{{ route('queue.resend', $t) }}">@csrf
                                <button type="submit" style="cursor:pointer;background:none;color:#9A8F97;font-weight:700;font-size:12px;padding:7px 8px;border:none;">↻ {{ __('pc.qt_resend') }}</button>
                            </form>
                        @else
                            <span style="font-size:12px;color:#B7A9B2;">{{ optional($t->closed_at)->format('H:i') }}</span>
                        @endif
                    </div>
                </div>
                @if (!empty($t->delivery))
                    <div style="padding:0 20px 10px;display:flex;gap:8px;flex-wrap:wrap;">
                        @foreach ($t->delivery as $key => $state)
                            <span style="font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:999px;color:#6B4257;background:#F7EEF3;">{{ __('pc.qt_msg_'.$key) }}: {{ $state }}</span>
                        @endforeach
                    </div>
                @endif
            @empty
                <div style="padding:26px 20px;text-align:center;font-size:13.5px;color:#9A8F97;border-top:1px solid #F3E7EE;">{{ __('pc.qt_empty') }}</div>
            @endforelse
        </div>
        </div>
    </div>
</x-staff-shell>
@endsection
