@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_assign_doctors'))

@php
    $statusMeta = \App\Support\RecordPresenter::statusMeta();
    $tints = ['#16A6A6','#2A6FDB','#7E4CC4','#F7941E','#E6017E','#43A047'];
    $selStyle  = 'padding:10px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:13.5px;background:#fff;min-width:180px;';
    $btnStyle  = 'cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:600;font-size:13px;padding:10px 16px;border:none;border-radius:10px;';
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
            @php($clinicMammographers = $mammographers->filter(fn($m) => $m->clinics->contains('id', $r->clinic_id)))
            @php($assignee = $r->activeAssignee())
            @php($canComplete = $r->status === \App\Models\PatientHistoryRecord::RETURNED)
            {{-- Default the role toggle to the OTHER role when a case comes back, to nudge the next hand-off. --}}
            @php($defaultRole = $r->assigned_role === 'doctor' ? 'mammographer' : 'doctor')
            <div x-data="{ role: '{{ $defaultRole }}' }" style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <div style="width:40px;height:40px;border-radius:50%;background:{{ $tints[$i % count($tints)] }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">{{ \Illuminate\Support\Str::substr($init,0,2) }}</div>
                <div style="flex:1;min-width:150px;">
                    <div style="font-weight:600;font-size:15px;">{{ $r->patient->full_name }}</div>
                    <div style="font-size:12.5px;color:#9A8F97;">{{ $r->ref_no }} · {{ $r->patient->dob?->age ?? '—' }} · {{ $r->patient->emirate ? __('pc.em_'.$r->patient->emirate) : '' }}</div>
                    @if ($assignee)
                        <div style="font-size:12px;color:#7E4CC4;margin-top:3px;">{{ __('pc.assigned_to_label') }} {{ __('pc.role_'.$r->assigned_role) }} · {{ $assignee->name }}</div>
                    @endif
                </div>
                <span style="font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px;color:{{ $st['c'] }};background:{{ $st['bg'] }};">{{ $st['label'] }}</span>

                {{-- Assign / reassign to a doctor or a mammographer in this clinic --}}
                <form method="POST" action="{{ route('clinic.assign.store') }}" style="display:flex;gap:8px;align-items:center;margin:0;flex-wrap:wrap;">
                    @csrf
                    <input type="hidden" name="record_id" value="{{ $r->id }}" />
                    <input type="hidden" name="role" :value="role" />
                    <div style="display:inline-flex;border:1px solid #E3D2DC;border-radius:10px;overflow:hidden;">
                        <button type="button" @click="role='doctor'" :style="role==='doctor' ? { background:'#F7E3EF', color:'#C0116E' } : { background:'#fff', color:'#6B4257' }" style="cursor:pointer;border:none;font-size:12.5px;font-weight:600;padding:9px 14px;white-space:nowrap;">{{ __('pc.role_doctor') }}</button>
                        <button type="button" @click="role='mammographer'" :style="role==='mammographer' ? { background:'#F7E3EF', color:'#C0116E' } : { background:'#fff', color:'#6B4257' }" style="cursor:pointer;border:none;border-inline-start:1px solid #E3D2DC;font-size:12.5px;font-weight:600;padding:9px 14px;white-space:nowrap;">{{ __('pc.role_mammographer') }}</button>
                    </div>

                    {{-- Doctor picker --}}
                    <select name="assignee_id" x-show="role==='doctor'" :required="role==='doctor'" :disabled="role!=='doctor'" x-cloak style="{{ $selStyle }}">
                        <option value="">— {{ __('pc.select_doctor') }} —</option>
                        @foreach ($clinicDoctors as $d)
                            <option value="{{ $d->id }}" @selected($r->assigned_role==='doctor' && $r->assigned_doctor_id === $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    {{-- Mammographer picker --}}
                    <select name="assignee_id" x-show="role==='mammographer'" :required="role==='mammographer'" :disabled="role!=='mammographer'" x-cloak style="{{ $selStyle }}">
                        <option value="">— {{ __('pc.select_mammographer') }} —</option>
                        @foreach ($clinicMammographers as $m)
                            <option value="{{ $m->id }}" @selected($r->assigned_role==='mammographer' && $r->mammographer_id === $m->id)>{{ $m->name }}</option>
                        @endforeach
                    </select>

                    <button type="submit" style="{{ $btnStyle }}">{{ __('pc.assign') }}</button>
                </form>

                {{-- Close the case once a role has returned it --}}
                @if ($canComplete)
                    <form method="POST" action="{{ route('clinic.complete', $r) }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="cursor:pointer;background:#E4F4EF;color:#2E7D32;font-weight:700;font-size:13px;padding:10px 16px;border:1px solid #BFE6D5;border-radius:10px;">✓ {{ __('pc.mark_completed') }}</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:14px;padding:34px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.no_records') }}</div>
        @endforelse
    </div>
</x-staff-shell>
@endsection
