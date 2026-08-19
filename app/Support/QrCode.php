<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Small wrapper around bacon/bacon-qr-code that returns an inline SVG QR code.
 * Inline SVG renders both in the browser (report document / print) and in mPDF
 * (stored PDF), so one code path serves both report renderers.
 */
class QrCode
{
    public static function svg(string $text, int $size = 132, int $margin = 1): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($text);

        // Drop the XML declaration so the SVG embeds cleanly inside HTML.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    /** The public verification URL a report's QR should point at (scan → auto-verify). */
    public static function verifyUrl(string $verifyCode, string $refNo): string
    {
        return rtrim((string) config('app.url'), '/')
            .'/verify?code='.urlencode($verifyCode).'&ref='.urlencode($refNo);
    }
}
