@extends('layouts.app')

@section('title', 'Pink Caravan — '.($clinic->exists ? __('pc.edit_clinic_title') : __('pc.add_clinic_title')))

@section('content')
@php
    $editing = $clinic->exists;
    $inputStyle = 'display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:14px;background:#fff;';
    $labelStyle = 'font-size:13px;font-weight:600;color:#6B4257;';
    $req = '<span style="color:#E6017E;">*</span>';
@endphp
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:820px;margin:0 auto;">
        <a href="{{ route('super.clinics.index') }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.nav_clinics') }}</a>

        @if ($errors->any())
            <div style="margin-bottom:16px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13.5px;">
                <ul style="margin:0;padding-inline-start:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('super.clinics.update', $clinic) : route('super.clinics.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">🏥</div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.clinic_details') }}</h3>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <label style="{{ $labelStyle }}">{{ __('pc.clinic_name') }} {!! $req !!}
                        <input type="text" name="name" value="{{ old('name', $clinic->name) }}" required style="{{ $inputStyle }}" />
                    </label>
                    <label style="{{ $labelStyle }}">{{ __('pc.code_label') }} {!! $req !!}
                        <input type="text" name="code" value="{{ old('code', $clinic->code) }}" required style="{{ $inputStyle }}" />
                    </label>

                    <label style="{{ $labelStyle }}">{{ __('pc.clinic_type') }} {!! $req !!}
                        <select name="type" required style="{{ $inputStyle }}">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $clinic->type?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="{{ $labelStyle }}">{{ __('pc.emirate') }} {!! $req !!}
                        <select name="emirate" required style="{{ $inputStyle }}">
                            @foreach ($emirates as $value => $label)
                                <option value="{{ $value }}" @selected(old('emirate', $clinic->emirate?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label style="grid-column:1 / -1;{{ $labelStyle }}">{{ __('pc.address') }}
                        <input type="text" name="address" value="{{ old('address', $clinic->address) }}" style="{{ $inputStyle }}" />
                    </label>

                    <label style="{{ $labelStyle }}">{{ __('pc.daily_capacity') }}
                        <input type="number" name="daily_capacity" min="0" value="{{ old('daily_capacity', $clinic->daily_capacity) }}" style="{{ $inputStyle }}" />
                        <span style="display:block;margin-top:5px;font-size:11.5px;font-weight:500;color:#9A8F97;">{{ __('pc.capacity_helper') }}</span>
                    </label>
                    <label style="{{ $labelStyle }}">{{ __('pc.contact_person') }}
                        <input type="text" name="contact_person" value="{{ old('contact_person', $clinic->contact_person) }}" style="{{ $inputStyle }}" />
                    </label>

                    <label style="{{ $labelStyle }}">{{ __('pc.contact_phone') }}
                        <input type="tel" name="contact_phone" value="{{ old('contact_phone', $clinic->contact_phone) }}" style="{{ $inputStyle }}" />
                    </label>

                    <div style="{{ $labelStyle }}">{{ __('pc.is_active') }}
                        <label x-data="{ on: {{ old('is_active', $clinic->is_active ?? true) ? 'true' : 'false' }} }"
                               style="display:flex;align-items:center;gap:12px;margin-top:10px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" x-model="on" style="display:none;" />
                            <span :style="on ? { background:'#E6017E' } : { background:'#D8C4CF' }"
                                  style="width:44px;height:26px;border-radius:999px;position:relative;transition:background .15s;display:inline-block;">
                                <span :style="on ? (document.dir==='rtl' ? { right:'20px', left:'auto' } : { left:'20px', right:'auto' }) : (document.dir==='rtl' ? { right:'2px', left:'auto' } : { left:'2px', right:'auto' })"
                                      style="position:absolute;top:2px;width:22px;height:22px;border-radius:50%;background:#fff;transition:all .15s;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>
                            </span>
                            <span style="font-size:13.5px;font-weight:600;color:#453A44;" x-text="on ? '{{ __('pc.active') }}' : '{{ __('pc.inactive') }}'"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Assigned Staff (doctors & nurses) — many-to-many, Phase 1b --}}
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);margin-top:16px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">👥</div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.assigned_staff') }}</h3>
                </div>
                <p style="font-size:12px;color:#9A8F97;margin:0 0 16px;">{{ __('pc.assigned_staff_help') }}</p>
                @php($assignedIds = collect(old('staff', $assigned))->map(fn ($v) => (int) $v)->all())
                @if (count($staff) === 0)
                    <p style="font-size:13px;color:#9A8F97;">{{ __('pc.no_staff_yet') }}</p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:10px;">
                        @foreach ($staff as $member)
                            <label class="pc-check">
                                <input type="checkbox" name="staff[]" value="{{ $member->id }}" @checked(in_array($member->id, $assignedIds, true)) />
                                <div>
                                    <div style="font-size:13.5px;font-weight:600;color:#2A2230;">{{ $member->name }}</div>
                                    <div style="font-size:11.5px;color:#9A8F97;">{{ $member->getRoleNames()->map(fn ($r) => __('pc.role_'.$r))->join(', ') }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;">
                <a href="{{ route('super.clinics.index') }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;text-decoration:none;background:#fff;">{{ __('pc.cancel') }}</a>
                <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">{{ __('pc.save_clinic') }}</button>
            </div>
        </form>
    </div>
</x-staff-shell>
@endsection
