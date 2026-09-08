@props([
    'name',
    'value'   => null,
    'minYear' => null,
    'maxYear' => null,
])

@php
    // Business feedback: month-by-month calendar paging was too slow. This field
    // offers dropdowns (day / month / year) plus a keypad-friendly typed entry;
    // it posts the same ISO value the old <input type="date"> did.
    $min = (int) ($minYear ?? 1900);
    $max = (int) ($maxYear ?? (now()->year + 1));

    $sel  = 'padding:10px 8px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;background:#fff;color:#2A2230;min-width:0;';
    $months = collect(range(1, 12))->map(fn ($m) => ['n' => $m, 'label' => __('pc.mon_'.$m)])->all();
@endphp

<div class="pc-datefield" style="margin-top:5px;"
     x-data="pcDateField(@js((string) ($value ?? '')), {{ $min }}, {{ $max }})">

    {{-- The single value the server sees. --}}
    <input type="hidden" name="{{ $name }}" :value="iso" />

    {{-- Mode 1 — dropdowns: pick day, month and year directly. --}}
    <div x-show="mode === 'pick'" style="display:grid;grid-template-columns:86px 1fr 104px;gap:6px;">
        {{-- Every option list is server-rendered: an x-for list is built after
             x-model applies, which would leave a preset date showing blank. --}}
        <select x-model="d" style="{{ $sel }}" aria-label="{{ __('pc.date_day') }}">
            <option value="">{{ __('pc.date_day') }}</option>
            @foreach (range(1, 31) as $day)
                <option value="{{ $day }}" :disabled="{{ $day }} > maxDay">{{ $day }}</option>
            @endforeach
        </select>
        <select x-model="m" @change="clampDay()" style="{{ $sel }}" aria-label="{{ __('pc.date_month') }}">
            <option value="">{{ __('pc.date_month') }}</option>
            @foreach ($months as $mo)
                <option value="{{ $mo['n'] }}">{{ $mo['label'] }}</option>
            @endforeach
        </select>
        <select x-model="y" @change="clampDay()" style="{{ $sel }}" aria-label="{{ __('pc.date_year') }}">
            <option value="">{{ __('pc.date_year') }}</option>
            @foreach (range($max, $min) as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>
    </div>

    {{-- Mode 2 — type it: inputmode="numeric" opens the keypad on phones/tablets. --}}
    <div x-show="mode === 'type'" x-cloak>
        <input type="text" x-ref="typedInput" x-model="typed" @input="onTyped()"
               inputmode="numeric" autocomplete="off" maxlength="10" placeholder="{{ __('pc.date_pattern') }}"
               aria-label="{{ __('pc.date_type_hint') }}"
               style="display:block;width:100%;padding:10px 11px;border:1px solid #E3D2DC;border-radius:9px;font-size:13.5px;letter-spacing:.04em;" />
    </div>

    <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
        <button type="button" @click="toggle()"
                style="cursor:pointer;background:none;border:none;padding:0;font-size:11.5px;font-weight:700;color:#E6017E;">
            <span x-show="mode === 'pick'">⌨ {{ __('pc.date_switch_type') }}</span>
            <span x-show="mode === 'type'" x-cloak>📋 {{ __('pc.date_switch_pick') }}</span>
        </button>
        <button type="button" @click="clear()" x-show="iso !== ''" x-cloak
                style="cursor:pointer;background:none;border:none;padding:0;font-size:11.5px;font-weight:600;color:#9A8F97;">
            ✕ {{ __('pc.date_clear') }}
        </button>
        <span x-show="mode === 'type'" x-cloak style="font-size:11px;color:#9A8F97;">{{ __('pc.date_type_hint') }}</span>
    </div>
</div>
