@extends('layouts.app')

@section('title', 'Pink Caravan — '.($user->exists ? __('pc.edit_user_title') : __('pc.add_user_title')))

@section('content')
@php
    $editing = $user->exists;
    $inputStyle = 'display:block;width:100%;margin-top:6px;padding:11px 12px;border:1px solid #E3D2DC;border-radius:10px;font-size:14px;background:#fff;';
    $labelStyle = 'font-size:13px;font-weight:600;color:#6B4257;';
    $req = '<span style="color:#E6017E;">*</span>';
    $assignedClinics = collect(old('clinics', $assigned))->map(fn ($v) => (int) $v)->all();
@endphp
<x-staff-shell :role="$sidebarRole" :route="$route">
    <div class="pc-anim" style="max-width:820px;margin:0 auto;">
        <a href="{{ route('super.users.index') }}" style="display:inline-block;margin-bottom:16px;font-size:13.5px;font-weight:600;color:#6B6472;text-decoration:none;">← {{ __('pc.back_to_users') }}</a>

        @if ($errors->any())
            <div style="margin-bottom:16px;background:#FBE4E4;border:1px solid #F3C4C4;border-radius:12px;padding:14px 18px;color:#9A2E2E;font-size:13.5px;">
                <ul style="margin:0;padding-inline-start:18px;">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('super.users.update', $user) : route('super.users.store') }}"
              x-data="{
                  role: @js($currentRole),
                  perms: @js(array_values((array) $selectedPerms)),
                  defaults: @js($roleDefaults),
                  syncRole() { this.perms = [...(this.defaults[this.role] || [])]; }
              }">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">🔐</div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ $editing ? __('pc.edit_user_title') : __('pc.add_user_title') }}</h3>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <label style="{{ $labelStyle }}">{{ __('pc.user_name') }} {!! $req !!}
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="{{ $inputStyle }}" />
                    </label>
                    <label style="{{ $labelStyle }}">{{ __('pc.user_email') }} {!! $req !!}
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="{{ $inputStyle }}" />
                    </label>
                    <label style="{{ $labelStyle }}">{{ __('pc.user_role') }} {!! $req !!}
                        <select name="role" required x-model="role" @change="syncRole()" style="{{ $inputStyle }}">
                            @foreach ($roles as $roleName)
                                <option value="{{ $roleName }}">{{ __('pc.role_'.$roleName) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="{{ $labelStyle }}">{{ $editing ? __('pc.user_password_edit') : __('pc.user_password') }} @if(!$editing){!! $req !!}@endif
                        <input type="password" name="password" @if(!$editing) required @endif autocomplete="new-password" style="{{ $inputStyle }}" />
                    </label>
                </div>

                <div style="margin-top:20px;{{ $labelStyle }}">{{ __('pc.user_clinics') }}
                    @if ($clinics->isEmpty())
                        <p style="font-size:13px;color:#9A8F97;margin-top:8px;">{{ __('pc.no_clinics_field') }}</p>
                    @else
                        <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:10px;margin-top:10px;">
                            @foreach ($clinics as $c)
                                <label class="pc-check">
                                    <input type="checkbox" name="clinics[]" value="{{ $c->id }}" @checked(in_array($c->id, $assignedClinics, true)) />
                                    <div>
                                        <div style="font-size:13.5px;font-weight:600;color:#2A2230;">{{ $c->name }}</div>
                                        <div style="font-size:11.5px;color:#9A8F97;">{{ $c->type->label() }} · {{ $c->emirate->label() }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Permissions (specific capabilities, per user) --}}
            <div style="background:#fff;border:1px solid #EFE2EA;border-radius:16px;padding:26px 28px;box-shadow:0 3px 14px rgba(120,60,90,.05);margin-top:16px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:#FCEFF5;color:#E6017E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">🛡️</div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;">{{ __('pc.permissions') }}</h3>
                </div>
                <p style="font-size:12px;color:#9A8F97;margin:0 0 16px;">{{ __('pc.permissions_help') }}</p>

                <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:18px;">
                    @foreach ($permGroups as $group => $perms)
                        <div>
                            <div style="font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9A8F97;margin-bottom:8px;">{{ __('pc.permgroup_'.$group) }}</div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @foreach ($perms as $perm)
                                    <label class="pc-check">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm }}" x-model="perms" />
                                        <span style="font-size:13px;font-weight:600;color:#453A44;">{{ __('pc.perm_'.$perm) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;">
                <a href="{{ route('super.users.index') }}" role="button" style="cursor:pointer;color:#6B6472;font-weight:600;padding:12px 22px;border:1px solid #E3D2DC;border-radius:11px;text-decoration:none;background:#fff;">{{ __('pc.cancel') }}</a>
                <button type="submit" style="cursor:pointer;background:linear-gradient(90deg,#E6017E,#C0116E);color:#fff;font-weight:700;padding:12px 26px;border:none;border-radius:11px;box-shadow:0 5px 15px rgba(230,1,126,.2);">{{ __('pc.save_user') }}</button>
            </div>
        </form>
    </div>
</x-staff-shell>
@endsection
