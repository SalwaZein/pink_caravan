<?php

namespace App\Support;

use App\Models\PatientHistoryRecord;
use App\Models\Report;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

class ReportService
{
    /**
     * Generate (or regenerate) the patient report PDF for a completed record,
     * store it, and record delivery state.
     *
     * Uses mPDF (not dompdf) so the bilingual EN/AR labels shape + lay out
     * correctly — dompdf cannot render Arabic script.
     */
    public static function generate(PatientHistoryRecord $record): Report
    {
        $record->loadMissing('patient', 'examination', 'referrals', 'clinic', 'doctor', 'nurse', 'mammographer');

        // Keep any existing verification code stable across re-generation; assign one
        // up-front so it can be printed on the PDF itself.
        $existing = Report::where('record_id', $record->id)->first();
        $verifyCode = $existing?->verify_code ?: self::uniqueVerifyCode();

        $verifyUrl = QrCode::verifyUrl($verifyCode, $record->ref_no);
        $html = view('reports.patient', [
            'record'     => $record,
            'verifyCode' => $verifyCode,
            'verifyUrl'  => $verifyUrl,
            'qr'         => QrCode::svg($verifyUrl, 120),
        ])->render();

        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'autoScriptToLang' => true,   // detect Arabic runs
            'autoLangToFont'   => true,   // pick an Arabic-capable font + shape RTL
            'tempDir'          => $tmp,
        ]);
        $mpdf->WriteHTML($html);

        $path = 'reports/'.$record->ref_no.'.pdf';
        Storage::put($path, $mpdf->Output('', Destination::STRING_RETURN));

        return Report::updateOrCreate(
            ['record_id' => $record->id],
            [
                'verify_code'  => $verifyCode,
                'path'         => $path,
                'generated_at' => now(),
                'delivery'     => ['email' => 'pending', 'sms' => 'pending', 'whatsapp' => 'pending', 'portal' => 'ready'],
            ],
        );
    }

    /** Generate a unique, human-readable verification code: V-XXXX-XXXX (no ambiguous chars). */
    private static function uniqueVerifyCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I,O,0,1
        do {
            $chunk = fn () => collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->join('');
            $code = 'V-'.$chunk().'-'.$chunk();
        } while (Report::where('verify_code', $code)->exists());

        return $code;
    }

    /** Ensure a report exists (regenerate if the file is missing). */
    public static function ensure(PatientHistoryRecord $record): Report
    {
        $report = $record->report;

        if (! $report || ! $report->path || ! Storage::exists($report->path)) {
            $report = self::generate($record);
        }

        // Backfill a verification code for reports created before verify codes existed.
        if (! $report->verify_code) {
            $report->update(['verify_code' => self::uniqueVerifyCode()]);
        }

        return $report;
    }

    /** Stream the stored PDF as a download. */
    public static function stream(PatientHistoryRecord $record, Report $report): Response
    {
        return response(Storage::get($report->path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="PinkCaravan-'.$record->ref_no.'.pdf"',
        ]);
    }
}
