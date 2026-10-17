<?php

declare(strict_types=1);

namespace Contenir\Preview\Laminas\Mvc;

use function strripos;
use function substr_replace;

/**
 * The small "Preview" badge a previewed page carries, so it is never mistaken for the live page:
 * placed before the page's closing body tag, on top of the page and taking no clicks.
 */
final class PreviewMarker
{
    public const string HTML =
        '<div role="status" data-contenir-preview style="position:fixed;z-index:2147483647;left:50%;'
            . 'bottom:1rem;transform:translateX(-50%);padding:.25rem .75rem;border-radius:999px;background:#111;color:#fff;'
            . 'font:600 12px/1.5 system-ui,sans-serif;opacity:.85;pointer-events:none">Preview</div>';

    /**
     * The page with the badge before its last closing body tag, or as it is without one.
     */
    public static function mark(string $html): string
    {
        $body = strripos($html, needle: '</body>');

        return false === $body ? $html : substr_replace($html, self::HTML, $body, length: 0);
    }
}
