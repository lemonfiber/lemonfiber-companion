<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One value a setting accepts: the value, what other settings have to hold for it to be a choice, and whether it is one now.
 */
final readonly class AChoice
{
    private function __construct(private string $value, private WhatAChoiceNeeds $needs, private bool $open) {}

    /** As the stack said it. */
    public static function of(string $value, WhatAChoiceNeeds $needs, bool $open): self
    {
        return new self($value, $needs, $open);
    }

    /** The value, exactly as the stack wrote it. */
    public function value(): string
    {
        return $this->value;
    }

    /** What other settings have to hold for it to be a choice. */
    public function needs(): WhatAChoiceNeeds
    {
        return $this->needs;
    }

    /** Whether it is a choice now, as the stack said. */
    public function isOpenNow(): bool
    {
        return $this->open;
    }
}
