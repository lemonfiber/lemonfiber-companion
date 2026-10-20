<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A challenge a stack issued for one mint, with the text this phone's key signs to prove it.
 *
 * Good for one mint, within a minute; asking again spends it.
 */
final readonly class AChallengeToSign
{
    private function __construct(private string $challenge, private ATextToSign $text) {}

    public static function of(string $challenge, ATextToSign $text): self
    {
        return new self($challenge, $text);
    }

    /** The challenge, as the stack issued it. */
    public function challenge(): string
    {
        return $this->challenge;
    }

    /** The text to sign. */
    public function text(): ATextToSign
    {
        return $this->text;
    }
}
