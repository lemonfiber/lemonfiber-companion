<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The exact text a stack asked this phone's key to sign. */
final readonly class ATextToSign
{
    private function __construct(private string $text) {}

    /** The text, as the stack will verify it. */
    public static function saying(string $text): self
    {
        return new self($text);
    }

    /** The text, to hand to the key. */
    public function said(): string
    {
        return $this->text;
    }
}
