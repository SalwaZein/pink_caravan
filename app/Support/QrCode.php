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
        $svg = preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);

        // Bacon hard-codes width/height (px) on the <svg>, so it ignores its container and
        // overflows. Strip them and let it scale to the box via the preserved viewBox.
        $svg = preg_replace('/(<svg\b[^>]*?)\s+width="[^"]*"/i', '$1', $svg, 1);
        $svg = preg_replace('/(<svg\b[^>]*?)\s+height="[^"]*"/i', '$1', $svg, 1);
        $svg = preg_replace('/<svg\b/i', '<svg width="100%" height="100%" preserveAspectRatio="xMidYMid meet" style="display:block"', $svg, 1);

        return $svg;
    }

    /** The public verification URL a report's QR should point at (scan → auto-verify). */
    public static function verifyUrl(string $verifyCode, string $refNo): string
    {
        return rtrim((string) config('app.url'), '/')
            .'/verify?code='.urlencode($verifyCode).'&ref='.urlencode($refNo);
    }
}
