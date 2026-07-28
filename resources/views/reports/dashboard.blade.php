<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #2A2230; font-size: 12px; }
    .wrap { padding: 28px 34px; }
    .head { border-bottom: 3px solid #E6017E; padding-bottom: 12px; margin-bottom: 18px; }
    .brand { font-size: 20px; font-weight: bold; color: #E6017E; }
    h1 { font-size: 15px; margin: 16px 0 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    td { padding: 8px 10px; border: 1px solid #EFE2EA; }
    td.k { background: #FAF4F7; color: #6B4257; font-weight: bold; width: 60%; }
    td.v { font-weight: bold; font-size: 14px; }
    .muted { color: #9A8F97; font-size: 10px; margin-top: 24px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head"><div class="brand">Pink Caravan — Campaign Report</div></div>
    <div class="muted" style="margin-top:0;">Generated {{ now()->format('d M Y H:i') }}</div>

    <h1>Campaign Metrics</h1>
    <table>
        <tr><td class="k">Total records</td><td class="v">{{ number_format($stats['total']) }}</td></tr>
        <tr><td class="k">Women screened (completed)</td><td class="v">{{ number_format($stats['completed']) }}</td></tr>
        <tr><td class="k">Pending cases</td><td class="v">{{ number_format($stats['pending']) }}</td></tr>
        <tr><td class="k">Normal results</td><td class="v">{{ number_format($stats['normal']) }} ({{ $stats['normalPct'] }}%)</td></tr>
        <tr><td class="k">Abnormal results</td><td class="v">{{ number_format($stats['abnormal']) }} ({{ $stats['abnormalRate'] }}%)</td></tr>
        <tr><td class="k">Referrals (closed)</td><td class="v">{{ number_format($stats['referrals']) }} ({{ $stats['referralsClosed'] }})</td></tr>
    </table>

    <h1>Throughput by Clinic</h1>
    <table>
        <tr><td class="k">Clinic</td><td class="v">Records</td></tr>
        @foreach ($stats['byClinic'] as $c)
            <tr><td class="k">{{ $c['name'] }} — {{ $c['em'] }}</td><td class="v">{{ $c['today'] }}</td></tr>
        @endforeach
    </table>

    <div class="muted">Pink Caravan Breast Cancer Awareness Campaign — Friends of Cancer Patients (FOCP). Confidential.</div>
</div>
</body>
</html>
