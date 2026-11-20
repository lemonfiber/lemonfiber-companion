<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function preg_match;

/**
 * A subject or an issuer, as the certificate names it and the stack wrote it out, and whether the stack cut it short.
 *
 * The stack writes every control character as a hex pair and bounds the
 * length, so the text is shown as it is. One that still holds a control
 * character did not come from a stack that keeps that promise, and is refused.
 */
final readonly class ACertificateName
{
    private function __construct(private string $text, private bool $cut) {}

    /** That name, cut short or whole. */
    public static function said(string $text, bool $cut): self
    {
        if (preg_match('/[\x00-\x1F\x7F]|\xC2[\x80-\x9F]/', $text) === 1) {
            throw CertificateSaysTooMuch::controlInIt();
        }

        return new self($text, $cut);
    }

    /** The name as the stack wrote it out. */
    public function text(): string
    {
        return $this->text;
    }

    /** Whether the stack cut it short. */
    public function isCut(): bool
    {
        return $this->cut;
    }
}
