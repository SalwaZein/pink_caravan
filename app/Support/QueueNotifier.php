<?php

namespace App\Support;

use App\Models\QueueToken;

/**
 * WhatsApp messages for the walk-in queue (business feedback #7).
 *
 * Each message is an approved WhatsApp template (see WhatsApp::TEMPLATES for the
 * variables); the bilingual text below is what the stub log records when the
 * API is not configured. The per-message result is stored on the token so the
 * desk can see what actually went out.
 */
class QueueNotifier
{
    /** Token issued — "you are number N, M people ahead of you". */
    public static function issued(QueueToken $token): string
    {
        $clinic = $token->clinic?->name ?? '';
        $ahead  = max(0, $token->aheadCount());

        return self::send($token, 'issued', 'queue_issued', [
            'clinic_name'  => $clinic,
            'token_code'   => $token->code,
            'token_number' => $token->number,
            'people_ahead' => $ahead,
        ],
            "Pink Caravan — {$clinic}\n"
            ."Your token is {$token->code} (number {$token->number}). "
            .($ahead > 0 ? "There are {$ahead} visitor(s) ahead of you. " : 'You are next. ')
            ."Please stay nearby — we will message you when it is your turn.\n\n"
            ."القافلة الوردية — {$clinic}\n"
            ."رقمك هو {$token->code} (الرقم {$token->number})."
            .($ahead > 0 ? " يوجد {$ahead} زائرة قبلك." : ' أنتِ التالية.')
            .' يرجى البقاء بالقرب وسنرسل لكِ رسالة عند حلول دورك.'
        );
    }

    /** Heads-up sent when the person before them walks into the clinic. */
    public static function approaching(QueueToken $token): string
    {
        $clinic = $token->clinic?->name ?? '';

        return self::send($token, 'approaching', 'queue_approaching', [
            'clinic_name' => $clinic,
            'token_code'  => $token->code,
        ],
            "Pink Caravan — {$clinic}\n"
            ."Your turn is approaching. Token {$token->code} — please make your way to the clinic entrance now.\n\n"
            ."القافلة الوردية — {$clinic}\n"
            ."اقترب دورك. رقمك {$token->code} — يرجى التوجه إلى مدخل العيادة الآن."
        );
    }

    /** The desk has called this token in. */
    public static function called(QueueToken $token): string
    {
        $clinic = $token->clinic?->name ?? '';

        return self::send($token, 'called', 'queue_called', [
            'clinic_name' => $clinic,
            'token_code'  => $token->code,
        ],
            "Pink Caravan — {$clinic}\n"
            ."It is your turn now. Token {$token->code} — please proceed to the clinic.\n\n"
            ."القافلة الوردية — {$clinic}\n"
            ."حان دورك الآن. رقمك {$token->code} — يرجى التوجه إلى العيادة."
        );
    }

    /**
     * Send + record the per-message status on the token's `delivery` map.
     *
     * @param  string  $key       delivery-map key shown on the desk (issued|approaching|called)
     * @param  string  $template  WhatsApp message key (WhatsApp::TEMPLATES)
     */
    private static function send(QueueToken $token, string $key, string $template, array $params, string $text): string
    {
        $token->loadMissing('clinic');

        $status = WhatsApp::send($token->whatsapp_number, $template, $params, $text);

        $token->delivery = array_merge($token->delivery ?? [], [
            $key => $status.' @ '.now()->format('H:i'),
        ]);
        $token->save();

        return $status;
    }
}
