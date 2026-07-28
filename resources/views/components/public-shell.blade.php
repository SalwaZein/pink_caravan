<div style="min-height:100vh;display:flex;flex-direction:column;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 32px;background:#fff;border-bottom:1px solid #EFE2EA;position:sticky;top:0;z-index:20;">
        <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:14px;">
            <img src="{{ asset('assets/pink-caravan-wordmark.png') }}" alt="Pink Caravan" style="height:46px;cursor:pointer;" />
        </a>
        <div style="display:flex;align-items:center;gap:10px;">
            <x-lang-toggle variant="public" />
            <a href="{{ url('/') }}" role="button" style="cursor:pointer;background:#fff;border:1px solid #EEDCE6;border-radius:999px;padding:7px 14px;font-weight:600;color:#6B6472;font-size:14px;text-decoration:none;">✕ {{ __('pc.exit') }}</a>
        </div>
    </div>
    <div style="flex:1;">
        {{ $slot }}
    </div>
</div>
