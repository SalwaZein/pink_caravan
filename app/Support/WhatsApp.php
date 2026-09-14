<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API — the FOCP number, reached through Outreachable's
 * proxy of the Meta Cloud API (POST …/v19.0/{phone_number_id}/messages).
 *
 * WhatsApp only lets a business START a conversation with an approved
 * template, and every message this platform sends is business-initiated (a
 * queue token, a report notice, a login code). So each message is a named
 * template whose variables are filled from TEMPLATES below, in order.
 *
 * Process on the Outreachable side: create the template, wait for Meta to
 * approve it, then put its name in the matching WHATSAPP_TPL_* env var.
 *
 * Returns the same status vocabulary as the other channels:
 *   'sent'        accepted by WhatsApp (message id logged)
 *   'logged'      no API configured — written to the log (dev / demo stub)
 *   'no_template' API configured, but this message's template is not set yet
 *   'skipped'     no phone number
 *   'failed'      the API rejected it (reason logged)
 */
class WhatsApp
{
    /**
     * Every message the platform sends, and the variables its template must
     * declare — {{1}}, {{2}}, … in exactly this order.
     */
    public const TEMPLATES = [
        // Walk-in queue (volunteer desk)
        'queue_issued'      => ['clinic_name', 'token_code', 'token_number', 'people_ahead'],
        'queue_approaching' => ['clinic_name', 'token_code'],
        'queue_called'      => ['clinic_name', 'token_code'],
        // Patient notifications
        'report_ready'      => ['patient_name', 'report_ref', 'portal_link'],
        'radiology_report'  => ['patient_name', 'report_ref', 'portal_link'],
        // Patient portal login — a Meta AUTHENTICATION template (code + copy-code button)
        'patient_otp'       => ['code'],
    ];

    /** Messages sent with an authentication template (the code also fills the button). */
    private const AUTHENTICATION = ['patient_otp'];

    /** Is the API itself configured (endpoint + token)? */
    public static function configured(): bool
    {
        return filled(config('services.whatsapp.url')) && filled(config('services.whatsapp.token'));
    }

    /** The approved template name configured for a message, if any. */
    public static function templateFor(string $key): ?string
    {
        $name = config("services.whatsapp.templates.{$key}");

        return filled($name) ? (string) $name : null;
    }

    /**
     * Send one message.
     *
     * @param  array<string, scalar|null>  $params        keyed by the names in TEMPLATES[$key]
     * @param  string                      $fallbackText  what the stub log records when nothing is sent
     */
    public static function send(?string $to, string $key, array $params, string $fallbackText): string
    {
        if (! array_key_exists($key, self::TEMPLATES)) {
            throw new \InvalidArgumentException("Unknown WhatsApp message [{$key}].");
        }

        $to = Messenger::normalise($to);

        if (! $to) {
            return 'skipped';
        }

        if (! self::configured()) {
            Log::info("[stub whatsapp] to {$to}: {$fallbackText}");

            return 'logged';
        }

        $template = self::templateFor($key);

        if (! $template) {
            Log::warning("[whatsapp] {$key} not sent to {$to}: no approved template configured (set WHATSAPP_TPL_".strtoupper($key).').');

            return 'no_template';
        }

        $payload = self::payload($to, $key, $template, $params);

        try {
            $response = Http::withToken((string) config('services.whatsapp.token'))
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post((string) config('services.whatsapp.url'), $payload);

            $messageId = $response->json('messages.0.id');

            if ($response->successful() && $messageId) {
                Log::info("[whatsapp] {$key} sent to {$to} ({$template}): {$messageId}");

                return 'sent';
            }

            $reason = $response->json('error.message') ?? $response->body();
            Log::warning("[whatsapp] {$key} to {$to} rejected (HTTP {$response->status()}): ".mb_substr((string) $reason, 0, 500));

            return 'failed';
        } catch (\Throwable $e) {
            Log::warning("[whatsapp] {$key} to {$to} failed: ".$e->getMessage());

            return 'failed';
        }
    }

    /**
     * The Meta Cloud API template payload, in the shape Outreachable documents.
     *
     * @param  array<string, scalar|null>  $params
     * @return array<string, mixed>
     */
    public static function payload(string $to, string $key, string $template, array $params): array
    {
        $values = array_map(
            fn (string $name) => self::cleanParam($params[$name] ?? null),
            self::TEMPLATES[$key],
        );

        $components = [[
            'type'       => 'body',
            'parameters' => array_map(fn (string $v) => ['type' => 'text', 'text' => $v], $values),
        ]];

        // Authentication templates carry the code a second time, on the copy-code button.
        if (in_array($key, self::AUTHENTICATION, true)) {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => 0,
                'parameters' => [['type' => 'text', 'text' => $values[0]]],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            // Meta wants the international number as digits only.
            'to'                => ltrim($to, '+'),
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['policy' => 'deterministic', 'code' => (string) config('services.whatsapp.language', 'en')],
                'components' => $components,
            ],
        ];
    }

    /**
     * Meta rejects template variables that are empty or contain new lines, tabs
     * or more than four consecutive spaces — so flatten and never send blank.
     */
    private static function cleanParam(mixed $value): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? '')));

        return $text === '' ? '-' : mb_substr($text, 0, 1000);
    }
}
