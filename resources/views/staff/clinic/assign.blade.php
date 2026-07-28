@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_assign_doctors'))

@php
    $statusMeta = \App\Support\RecordPresenter::statusMeta();
    $tints = ['#16A6A6','#2A6FDB','#7E4CC4','#F7941E','#E6017E','#43A047'];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="display:flex;flex-direction:column;gap:12px;">
        @if (session('status'))
            <div style="margin-bottom:4px;display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:13px;font-weight:700;padding:10px 16px;border-radius:12px;align-self:flex-start;">
                <span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span>{{ session('status') }}
            </div>
        @endif

        @forelse ($records as $i => $r)
            @php($st = $statusMeta[$r->status] ?? $statusMeta['submitted'])
            @php($init = \Illuminate\Support\Str::of($r->patient->full_name)->explode(' ')->map(fn($w)=>\Illuminate\Support\Str::substr($w,0,1))->join(''))
            @php($clinicDoctors = $doctors->filter(fn($d) => $d->clinics->contains('id', $r->clinic_id)))
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <div style="width:40px;height:40px;border-radius:50%;background:{{ $tints[$i % count($tints)] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">{{ \Illuminate\Support\Str::substr($init,0,2) }}</div>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:15px;">{{ $r->patient->full_name }}</div>
                    <div style="font-size:12.5px;color:#9A8F97;">{{ $r->ref_no }} · {{ $r->patient->dob?->age ?? '—' }} · {{ $r->patient->emirate ? __('pc.em_'.$r->patient->emirate) : '' }}</div>
                </div>
                <span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $st['c'] }};background:{{ $st['bg'] }};">{{ $st['label'] }}</span>
                <form method="POST" action="{{ route('clinic.assign.store') }}" style="display:flex;gap:8px;align-items:center;margin:0;">
                    @csrf
                    <input type="hidden" name="record_id" value="{{ $r->id }}" />
                    <select name="doctor_id" required style="padding:10px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:13.5px;background:#fff;min-width:180px;">
                        <option value="">— {{ __('pc.select_doctor') }} —</option>
                        @foreach ($clinicDoctors as $d)
                            <option value="{{ $d->id }}" @selected($r->assigned_doctor_id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:600;font-size:13px;padding:10px 16px;border:none;border-radius:10px;">{{ __('pc.assign') }}</button>
                </form>
            </div>
        @empty
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:34px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.no_records') }}</div>
        @endforelse
    </div>
</x-staff-shell>
@endsection
