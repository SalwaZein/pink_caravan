@props(['variant' => 'staff'])

@php
    $other = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $href  = url('/lang/'.$other);
    $label = __('pc.lang_label');

    $styles = [
        'hub'    => 'background:#fff;border:1px solid #EEDCE6;color:#E6017E;padding:8px 16px;box-shadow:0 2px 10px rgba(230,1,126,.06);font-size:15px;',
        'public' => 'background:#FCEFF5;color:#E6017E;padding:7px 14px;font-size:14px;',
        'staff'  => 'background:#FCEFF5;color:#E6017E;padding:8px 13px;font-size:13.5px;',
    ];
@endphp

<a href="{{ $href }}" role="button"
   style="cursor:pointer;user-select:none;display:inline-flex;align-items:center;gap:8px;border-radius:999px;font-weight:600;text-decoration:none;{{ $styles[$variant] ?? $styles['staff'] }}">
    <span>🌐</span><span>{{ $label }}</span>
</a>
