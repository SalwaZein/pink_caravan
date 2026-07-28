<?php

namespace App\Support;

use App\Mail\ReportReadyMail;
use App\Models\PatientHistoryRecord;
use Illuminate\Support\Facades\Http;
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
            'whatsapp' => self::gateway('whatsapp', $patient?->mobile1, $body),
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

    private static function email(?string $to, PatientHistoryRecord $record, string $link): string
    {
        if (! $to) {
            return 'skipped';
        }

        try {
            Mail::to($to)->send(new ReportReadyMail($record, $link));

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning("[report-ready email] failed for {$record->ref_no}: ".$e->getMessage());

            return 'failed';
        }
    }

    /** Generic SMS/WhatsApp dispatch — configured HTTP gateway, or a logged stub. */
    private static function gateway(string $channel, ?string $to, string $body): string
    {
        if (! $to) {
            return 'skipped';
        }

        $cfg = (array) config("services.{$channel}");
        $url = $cfg['url'] ?? null;

        if (! $url) {
            Log::info("[stub {$channel}] to {$to}: {$body}");

            return 'logged';
        }

        try {
            $req = Http::asJson();
            if (! empty($cfg['token'])) {
                $req = $req->withToken($cfg['token']);
            }
            $res = $req->post($url, array_filter([
                'to'   => $to,
                'from' => $cfg['from'] ?? null,
                'body' => $body,
                'text' => $body,
            ]));

            return $res->successful() ? 'sent' : 'failed';
        } catch (\Throwable $e) {
            Log::warning("[{$channel}] send failed to {$to}: ".$e->getMessage());

            return 'failed';
        }
    }
}
