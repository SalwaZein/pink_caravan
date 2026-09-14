<?php

namespace App\Support;

use App\Mail\RadiologyReportMail;
use App\Mail\ReportReadyMail;
use App\Models\PatientHistoryRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends patient-facing notifications across email + SMS + WhatsApp.
 *
 * Email uses Laravel's mail system directly (real when SMTP is configured, logs
 * with MAIL_MAILER=log in dev). SMS/WhatsApp POST to a configurable gateway
 * (services.sms / services.whatsapp `url`); when no url is set they log — the
 * same stub pattern as the OTP flow — so the flow works end to end without a
 * provider and needs only credentials to go live.
 */
class PatientNotifier
{
    /**
     * Tell the patient their report is ready. Returns per-channel status:
     * 'sent' | 'logged' | 'skipped' | 'failed'.
     *
     * @return array{email:string, sms:string, whatsapp:string}
     */
    public static function reportReady(PatientHistoryRecord $record): array
    {
        $record->loadMissing('patient');
        $patient = $record->patient;
        $link = self::portalLink();
        $body = self::messageText($record->ref_no, $link);

        return [
            'email'    => self::email($patient?->email, $record, $link),
            'sms'      => self::gateway('sms', $patient?->mobile1, $body),
            'whatsapp' => WhatsApp::send($patient?->mobile1, 'report_ready', [
                'patient_name' => $patient?->full_name,
                'report_ref'   => $record->ref_no,
                'portal_link'  => $link,
            ], $body),
        ];
    }

    /**
     * Send the radiologist's final PDF report to the patient: the document itself
     * by email (attached), plus an SMS/WhatsApp notice pointing at the portal.
     *
     * @return array{email:string, sms:string, whatsapp:string}
     */
    public static function radiologyReport(PatientHistoryRecord $record): array
    {
        $record->loadMissing('patient');
        $patient = $record->patient;
        $link = self::portalLink();
        $body = self::radiologyMessageText($record->ref_no, $link);

        return [
            'email'    => self::send($patient?->email, $record, fn () => new RadiologyReportMail($record, $link), 'radiology-report'),
            'sms'      => self::gateway('sms', $patient?->mobile1, $body),
            'whatsapp' => WhatsApp::send($patient?->mobile1, 'radiology_report', [
                'patient_name' => $patient?->full_name,
                'report_ref'   => $record->ref_no,
                'portal_link'  => $link,
            ], $body),
        ];
    }

    /** Absolute link to the patient OTP portal (they still verify with a one-time code). */
    private static function portalLink(): string
    {
        return rtrim((string) config('app.url'), '/').'/patient';
    }

    private static function messageText(string $ref, string $link): string
    {
        return "Pink Caravan: your Clinical Breast Examination report {$ref} is ready. "
            ."Open it securely (you'll get a one-time code): {$link} — "
            ."القافلة الوردية: تقرير الفحص السريري {$ref} جاهز. افتحيه عبر رمز تحقق: {$link}";
    }

    private static function radiologyMessageText(string $ref, string $link): string
    {
        return "Pink Caravan: your mammography report {$ref} has been sent to your email as a PDF. "
            ."You can also open it here: {$link} — "
            ."القافلة الوردية: تم إرسال تقرير التصوير الشعاعي {$ref} إلى بريدك الإلكتروني بصيغة PDF. ويمكنك فتحه هنا: {$link}";
    }

    private static function email(?string $to, PatientHistoryRecord $record, string $link): string
    {
        return self::send($to, $record, fn () => new ReportReadyMail($record, $link), 'report-ready');
    }

    /**
     * Deliver one mailable, reporting 'sent' | 'skipped' (no address) | 'failed'.
     * The mailable is built lazily so a missing address costs nothing.
     *
     * @param  callable():\Illuminate\Mail\Mailable  $mailable
     */
    private static function send(?string $to, PatientHistoryRecord $record, callable $mailable, string $label): string
    {
        if (! $to) {
            return 'skipped';
        }

        try {
            Mail::to($to)->send($mailable());

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning("[{$label} email] failed for {$record->ref_no}: ".$e->getMessage());

            return 'failed';
        }
    }

    /** Generic SMS/WhatsApp dispatch — delegated to the shared gateway helper. */
    private static function gateway(string $channel, ?string $to, string $body): string
    {
        return Messenger::send($channel, $to, $body);
    }

}
