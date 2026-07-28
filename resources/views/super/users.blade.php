@extends('layouts.app')
@section('title', 'Pink Caravan — '.__('pc.nav_users_roles'))

@php
    $roleTint = ['super_admin' => '#7E4CC4', 'clinic_admin' => '#F7941E', 'doctor' => '#2A6FDB', 'nurse' => '#16A6A6'];
@endphp

@section('content')
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim">
        @if (session('status'))
            <div style="margin-bottom:16px;display:inline-flex;align-items:center;gap:8px;background:#E4F4EF;color:#2E7D32;font-size:13px;font-weight:700;padding:10px 16px;border-radius:12px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#2E7D32;"></span>{{ session('status') }}
            </div>
        @endif

        <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
            <a href="{{ route('super.users.create') }}" role="button" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:600;font-size:14px;padding:11px 20px;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);text-decoration:none;">+ {{ __('pc.add_user') }}</a>
        </div>

        <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;overflow:hidden;box-shadow:0 3px 14px rgba(120,60,90,.05);">
            <div style="display:grid;grid-template-columns:1.3fr 1.2fr 1.4fr 90px;gap:12px;padding:14px 20px;background:#FAF4F7;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A8F97;"><div>{{ __('pc.patient_col') }}</div><div>{{ __('pc.role_col') }}</div><div>{{ __('pc.clinic_name_col') }}</div><div style="text-align:end;">{{ __('pc.actions') }}</div></div>
            @forelse ($users as $u)
                @php($role = $u->getRoleNames()->first())
                @php($initials = \Illuminate\Support\Str::of($u->name)->replace('Dr. ', '')->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->join(''))
                <div style="display:grid;grid-template-columns:1.3fr 1.2fr 1.4fr 90px;gap:12px;align-items:center;padding:13px 20px;border-top:1px solid #F3E7EE;font-size:13.5px;">
                    <div style="display:flex;align-items:center;gap:10px;"><div style="width:34px;height:34px;border-radius:50%;background:{{ $roleTint[$role] ?? '#E6017E' }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12.5px;">{{ \Illuminate\Support\Str::substr($initials, 0, 2) }}</div><div><div style="font-weight:600;">{{ $u->name }}</div><div style="font-size:11.5px;color:#9A8F97;">{{ $u->email }}</div></div></div>
                    <div style="color:#6B6472;font-size:12.5px;">{{ $role ? __('pc.role_'.$role) : '—' }}</div>
                    <div style="color:#6B6472;font-size:12.5px;">{{ $u->clinics->pluck('name')->join(', ') ?: __('pc.unassigned') }}</div>
                    <div style="text-align:end;"><a href="{{ route('super.users.edit', $u) }}" role="button" style="cursor:pointer;font-size:12.5px;font-weight:700;color:#E6017E;padding:6px 12px;border:1px solid #F6BFD9;border-radius:8px;text-decoration:none;">{{ __('pc.edit') }}</a></div>
                </div>
            @empty
                <div style="padding:34px 20px;text-align:center;color:#9A8F97;font-size:14px;">{{ __('pc.no_users') }}</div>
            @endforelse
        </div>
    </div>
</x-staff-shell>
@endsection
