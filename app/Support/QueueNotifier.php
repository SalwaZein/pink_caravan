<?php

namespace App\Support;

use App\Models\QueueToken;

/**
 * WhatsApp messages for the walk-in queue (business feedback #7).
 *
 * Every message is bilingual (EN · AR) and sent through the shared gateway, so
 * it logs as a stub until WHATSAPP_GATEWAY_URL is configured. The per-message
 * result is stored on the token so the desk can see what actually went out.
 */
class QueueNotifier
{
    /** Token issued — "you are number N, M people ahead of you". */
    public static function issued(QueueToken $token): string
    {
        $clinic = $token->clinic?->name ?? '';
        $ahead  = max(0, $token->aheadCount());

        return self::send($token, 'issued',
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

        return self::send($token, 'approaching',
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

        return self::send($token, 'called',
            "Pink Caravan — {$clinic}\n"
            ."It is your turn now. Token {$token->code} — please proceed to the clinic.\n\n"
            ."القافلة الوردية — {$clinic}\n"
            ."حان دورك الآن. رقمك {$token->code} — يرجى التوجه إلى العيادة."
        );
    }

    /** Send + record the per-message status on the token's `delivery` map. */
    private static function send(QueueToken $token, string $key, string $body): string
    {
        $token->loadMissing('clinic');

        $status = Messenger::whatsapp($token->whatsapp_number, $body);

        $token->delivery = array_merge($token->delivery ?? [], [
            $key => $status.' @ '.now()->format('H:i'),
        ]);
        $token->save();

        return $status;
    }
}
