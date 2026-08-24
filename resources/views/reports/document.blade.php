<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pink Caravan — {{ $vm['data']['refNo'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { background: #F4EEF1; font-family: 'IBM Plex Sans', 'IBM Plex Sans Arabic', system-ui, sans-serif; color: #2A2230; }
  [dir="rtl"] { font-family: 'IBM Plex Sans Arabic', 'IBM Plex Sans', system-ui, sans-serif; }
  .ar { font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; }
  @media print {
    body { background: #fff; }
    .noprint { display: none !important; }
    .doc { box-shadow: none !important; border: 0 !important; margin: 0 !important; width: 100% !important; border-radius: 0 !important; }
    @page { size: A4; margin: 11mm 10mm 16mm; }

    /* Pagination: never split a section, a table row or the header block across
       two pages, and never leave a heading stranded at the foot of a page. */
    .sec, .row, .head { break-inside: avoid; page-break-inside: avoid; }
    h3, .lede { break-after: avoid; page-break-after: avoid; }

    /* Repeat the confidentiality strip at the foot of every printed page. */
    .docfoot { position: fixed; left: 0; right: 0; bottom: 0; border-top: 1px solid #F3E7EE; }
    .pagepad { padding-bottom: 12mm; }
  }
</style>
</head>
<body>
<div dir="{{ $vm['dir'] }}" style="min-height: 100vh; padding: 26px 20px 60px; background: #F4EEF1; direction: {{ $vm['dir'] }};">

  <div class="noprint" style="max-width: 860px; margin: 0 auto 16px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 16px;">
    <div style="min-width: 240px; flex: 1 1 260px;">
      <div style="font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #E6017E;">Pink Caravan · Report output</div>
      <div style="font-size: 13px; color: #6B6472; margin-top: 4px;">Bilingual document — every label in English and Arabic</div>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
      @isset($back)
        <a href="{{ $back }}" style="text-decoration:none; font-size: 12.5px; font-weight: 700; padding: 9px 16px; border-radius: 999px; background:#fff; border:1px solid #E3D2DC; color:#6B4257;">← Back</a>
      @endisset
      <a href="{{ $download ?? '#' }}" @if(empty($download)) onclick="window.print();return false;" @endif style="text-decoration:none; cursor: pointer; font-size: 12.5px; font-weight: 700; padding: 9px 16px; border-radius: 999px; background: linear-gradient(90deg,#E6017E,#C0116E); color: #fff; box-shadow: 0 4px 14px rgba(230,1,126,.2);">🖨 Print / PDF</a>
    </div>
  </div>

  <div class="doc" style="max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #EFE2EA; border-radius: 6px; box-shadow: 0 10px 34px rgba(120,60,90,.10); overflow: hidden;">

    <div class="head" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 26px 34px 22px; border-bottom: 3px solid #E6017E;">
      <div style="display: flex; align-items: center; gap: 18px;">
        <img src="{{ asset('assets/focp-pc-logo.svg') }}" alt="Friends of Cancer Patients — Pink Caravan" style="height: 58px; width: auto; flex-shrink: 0;" />
        <div>
          <div style="font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #E6017E;">{{ $vm['f']['org'] }}</div>
          <div style="font-size: 19px; font-weight: 700; margin-top: 5px; letter-spacing: -.01em;">{{ $vm['f']['title'] }}</div>
          <div class="ar" style="font-size: 13.5px; color: #6B6472; margin-top: 3px;">{{ $vm['f']['titleAlt'] }}</div>
        </div>
      </div>
      <div style="text-align: end; flex-shrink: 0;">
        <div style="font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #9A8F97;">{{ $vm['f']['ref'] }}</div>
        <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 15px; font-weight: 700; margin-top: 3px;">{{ $vm['data']['refNo'] }}</div>
        <div style="font-size: 11.5px; color: #9A8F97; margin-top: 4px;"><span style="unicode-bidi: isolate;">{{ $vm['f']['issued'] }}</span> <span style="unicode-bidi: isolate; direction: ltr;">{{ $vm['data']['issuedAt'] }}</span></div>
        <div style="display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 11px; font-weight: 700; color: #2E7D32; background: #E4F4EF; padding: 5px 11px; border-radius: 999px; white-space: nowrap; max-width: 100%;">✓ <span style="unicode-bidi: isolate;">{{ $vm['f']['status'] }}</span></div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #F3E7EE; border-bottom: 1px solid #F3E7EE;">
      @foreach ($vm['meta'] as $m)
        <div style="background: #FDFAFC; padding: 13px 34px;">
          <div style="font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #B7A9B2;">{{ $m['k'] }}</div>
          <div style="font-size: 14px; font-weight: 600; margin-top: 3px;"><span style="unicode-bidi: isolate; direction: {{ $m['dir'] }};">{{ $m['v'] }}</span></div>
        </div>
      @endforeach
    </div>

    <div class="pagepad" style="padding: 24px 34px 30px; display: flex; flex-direction: column; gap: 24px;">

      {{-- Result + recommendation on a single compact line (next steps close the report). --}}
      <div class="sec">
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; border: 1px solid #F0E1E9; border-inline-start: 4px solid {{ $vm['resultColor'] }}; border-radius: 12px; padding: 12px 16px; background: {{ $vm['resultBg'] }};">
          <span style="font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #6B6472;">{{ $vm['f']['result'] }}</span>
          <span style="font-size: 15px; font-weight: 700; color: {{ $vm['resultColor'] }};">{{ $vm['resultText'] }}</span>
          <span style="color: #D3BFCC;">|</span>
          <span style="font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #6B6472;">{{ $vm['f']['recommendation'] }}</span>
          <span style="font-size: 14px; font-weight: 600; color: #2A2230;">{{ $vm['recText'] }}</span>
        </div>
        @if ($vm['resultNote'])
          <div style="font-size: 12px; color: #6B6472; line-height: 1.55; margin-top: 7px; padding-inline-start: 2px;">{{ $vm['resultNote'] }}</div>
        @endif
      </div>

      <div class="sec">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
          <h3 style="margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6B4257;">{{ $vm['f']['findings'] }}</h3>
          <div style="flex: 1; height: 1px; background: #F3E7EE;"></div>
        </div>
        @if ($vm['hasFindings'])
          <div style="border: 1px solid #F0E1E9; border-radius: 12px; overflow: hidden;">
            <div style="display: grid; grid-template-columns: 1.6fr 120px 140px; gap: 12px; padding: 9px 16px; background: #FAF4F7; font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #9A8F97;">
              <div>{{ $vm['f']['findingCol'] }}</div><div>{{ $vm['f']['typeCol'] }}</div><div>{{ $vm['f']['sideCol'] }}</div>
            </div>
            @foreach ($vm['findings'] as $fi)
              <div class="row" style="display: grid; grid-template-columns: 1.6fr 120px 140px; gap: 12px; align-items: center; padding: 10px 16px; border-top: 1px solid #F5EBF1; font-size: 13px;">
                <div style="font-weight: 600;">{{ $fi['label'] }}</div>
                <div><span style="font-size: 10.5px; font-weight: 700; padding: 3px 9px; border-radius: 999px; color: {{ $fi['catC'] }}; background: {{ $fi['catBg'] }};">{{ $fi['cat'] }}</span></div>
                <div style="font-size: 12.5px; color: #6B6472;">{{ $fi['side'] }}</div>
              </div>
            @endforeach
          </div>
        @else
          <div style="border: 1px dashed #E3D2DC; border-radius: 12px; padding: 14px 16px; font-size: 13px; color: #6B6472; background: #FDFAFC;">{{ $vm['f']['findingsEmpty'] }}</div>
        @endif
      </div>

      <div class="sec">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
          <h3 style="margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6B4257;">{{ $vm['f']['map'] }}</h3>
          <div style="flex: 1; height: 1px; background: #F3E7EE;"></div>
        </div>
        <div style="border: 1px solid #F0E1E9; border-radius: 12px; padding: 16px; background: #FDFAFC;">
          {{-- The pin layer is exactly the diagram box, matching the doctor's exam form, so a pin
               lands on the same spot the doctor marked. Do not pad or resize one without the other. --}}
          <div style="position: relative; width: 420px; max-width: 100%; margin: 0 auto;">
            <svg viewBox="0 0 420 200" style="display: block; width: 100%; height: auto;">
              <text x="105" y="22" text-anchor="middle" font-size="13" font-weight="700" fill="#B7A9B2" font-family="IBM Plex Sans">R</text>
              <text x="315" y="22" text-anchor="middle" font-size="13" font-weight="700" fill="#B7A9B2" font-family="IBM Plex Sans">L</text>
              <circle cx="105" cy="115" r="72" fill="#fff" stroke="#E0C9D5" stroke-width="2"/>
              <circle cx="105" cy="115" r="12" fill="none" stroke="#D3A9BF" stroke-width="2"/>
              <circle cx="315" cy="115" r="72" fill="#fff" stroke="#E0C9D5" stroke-width="2"/>
              <circle cx="315" cy="115" r="12" fill="none" stroke="#D3A9BF" stroke-width="2"/>
              <line x1="33" y1="115" x2="177" y2="115" stroke="#F1DEE8" stroke-width="1"/>
              <line x1="105" y1="43" x2="105" y2="187" stroke="#F1DEE8" stroke-width="1"/>
              <line x1="243" y1="115" x2="387" y2="115" stroke="#F1DEE8" stroke-width="1"/>
              <line x1="315" y1="43" x2="315" y2="187" stroke="#F1DEE8" stroke-width="1"/>
            </svg>
            @foreach ($vm['pins'] as $pin)
              <div style="position: absolute; left: {{ $pin['left'] }}; top: {{ $pin['top'] }}; transform: translate(-50%,-50%); width: 22px; height: 22px; border-radius: 50%; background: #E6017E; color: #fff; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(230,1,126,.35); border: 2px solid #fff;">{{ $pin['n'] }}</div>
            @endforeach
          </div>

          {{-- Pin notes run along the page under the diagram (no side panel). --}}
          @if ($vm['hasPins'])
            <div style="display: flex; flex-wrap: wrap; gap: 9px 26px; margin-top: 14px; padding-top: 13px; border-top: 1px solid #F3E7EE;">
              @foreach ($vm['pins'] as $pin)
                <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #453A44;">
                  <span style="width: 20px; height: 20px; flex-shrink: 0; border-radius: 50%; background: #FCE7F0; color: #E6017E; font-weight: 700; font-size: 11px; display: flex; align-items: center; justify-content: center;">{{ $pin['n'] }}</span>
                  <span>{{ $pin['label'] }}</span>
                </div>
              @endforeach
            </div>
          @else
            <div style="font-size: 12.5px; color: #9A8F97; line-height: 1.5; margin-top: 14px; padding-top: 13px; border-top: 1px solid #F3E7EE;">{{ $vm['f']['mapEmpty'] }}</div>
          @endif
        </div>
      </div>

      <div class="sec">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
          <h3 style="margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6B4257;">{{ $vm['f']['notes'] }}</h3>
          <div style="flex: 1; height: 1px; background: #F3E7EE;"></div>
        </div>
        <p style="margin: 0; font-size: 13.5px; line-height: 1.65; color: #453A44; background: #FDFAFC; border: 1px solid #F0E1E9; border-radius: 12px; padding: 14px 16px;">{{ $vm['notesText'] }}</p>
      </div>

      {{-- Next steps — the last thing the patient reads before the signature block. --}}
      <div class="sec">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
          <h3 style="margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6B4257;">{{ $vm['f']['nextSteps'] }}</h3>
          <div style="flex: 1; height: 1px; background: #F3E7EE;"></div>
        </div>
        <div style="border: 1px solid #F0E1E9; border-radius: 12px; padding: 14px 16px; background: #FDFAFC; display: flex; flex-direction: column; gap: 9px;">
          @foreach ($vm['nextSteps'] as $i => $st)
            <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 13px; color: #453A44; line-height: 1.5;">
              <span style="flex-shrink: 0; width: 20px; height: 20px; border-radius: 50%; background: #FCE7F0; color: #E6017E; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center;">{{ $i + 1 }}</span>
              <span>{{ $st }}</span>
            </div>
          @endforeach
        </div>
      </div>

      <div class="sec" style="display: grid; grid-template-columns: 1fr 240px; gap: 16px; align-items: stretch; border-top: 1px solid #F3E7EE; padding-top: 20px;">
        <div style="display: flex; flex-direction: column;">
          <div style="font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #B7A9B2; margin-bottom: 10px;">{{ $vm['f']['careTeam'] }}</div>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; align-items: stretch;">
            @foreach ($vm['team'] as $tm)
              <div style="display: flex; flex-direction: column;">
                <div style="font-size: 11.5px; color: #9A8F97; line-height: 1.35; min-height: 32px;">{{ $tm['role'] }}</div>
                <div style="font-size: 13.5px; font-weight: 700; margin-top: 3px; min-height: 19px;">{{ $tm['name'] ?? '—' }}</div>
                <div style="font-size: 11.5px; color: #B7A9B2; margin-top: 6px;"><span style="unicode-bidi: isolate; direction: ltr;">{{ $tm['at'] }}</span></div>
                <div style="height: 1px; background: #E3D2DC; margin-top: 8px;"></div>
              </div>
            @endforeach
          </div>
          <div style="font-size: 11.5px; color: #9A8F97; margin-top: 12px;">🔐 <span style="unicode-bidi: isolate;">{{ $vm['f']['attested'] }}</span> <span style="unicode-bidi: isolate; direction: ltr;">{{ $vm['data']['attestedAt'] }}</span></div>
        </div>
        <div style="border: 1px solid #F0E1E9; border-radius: 12px; padding: 12px 14px; background: #FAF4F7; align-self: start; margin-top: 25px; display: flex; gap: 14px; align-items: center;">
          <div style="flex-shrink: 0; width: 92px; height: 92px; background: #fff; border: 1px solid #F0E1E9; border-radius: 8px; padding: 5px;">{!! $vm['data']['qr'] !!}</div>
          <div>
            <div style="font-size: 10.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #B7A9B2;">{{ $vm['f']['verify'] }}</div>
            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 16px; font-weight: 700; letter-spacing: .06em; color: #E6017E; margin-top: 5px;">{{ $vm['data']['verifyCode'] }}</div>
            <div style="font-size: 11px; color: #9A8F97; margin-top: 5px; line-height: 1.45;">{{ $vm['f']['verifyHint'] }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="docfoot" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 34px; background: #FAF4F7; border-top: 1px solid #F3E7EE; font-size: 11px; color: #9A8F97;">
      <span style="max-width: 70%; line-height: 1.5;">{{ $vm['f']['confidential'] }}</span>
      {{-- No page number: browsers can't fill @page margin boxes, so a hardcoded
           "Page 1 of 1" lies as soon as the report runs onto a second sheet. --}}
      <span style="font-weight: 600; flex-shrink: 0;">{{ $vm['data']['refNo'] }}</span>
    </div>
  </div>
</div>
</body>
</html>
