<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The SMS gateway, plus phone-number normalisation shared by every channel.
 *
 * When services.sms `url` is configured the message is POSTed to it with a
 * bearer token if provided; otherwise it is logged (stub), so every flow works
 * end to end without a provider and needs only credentials to go live.
 *
 * WhatsApp has its own template-based driver: App\Support\WhatsApp.
 *
 * Returns 'sent' | 'logged' | 'skipped' | 'failed'.
 */
class Messenger
{
    public static function sms(?string $to, string $body): string
    {
        return self::send('sms', $to, $body);
    }

    public static function send(string $channel, ?string $to, string $body): string
    {
        $to = self::normalise($to);

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

    /**
     * Tidy a number typed at the clinic door: strip spaces/dashes and turn a
     * local UAE format (05x…) into international (+9715x…). Anything already
     * carrying a country code is left alone.
     */
    public static function normalise(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $number) ?? '';

        if ($digits === '' || $digits === '+') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }

        if (! str_starts_with($digits, '+')) {
            $digits = str_starts_with($digits, '0')
                ? '+971'.ltrim($digits, '0')   // 050 1234567 → +97150 1234567
                : '+'.$digits;
        }

        return $digits;
    }
}
