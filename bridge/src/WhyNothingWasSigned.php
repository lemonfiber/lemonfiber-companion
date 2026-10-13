<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why a phone key signed nothing, in the device's own word.
 *
 * Mirrors `PhoneKeyRule.kt` and `PhoneKeyRule.swift`.
 */
enum WhyNothingWasSigned: string
{
    /** The operator declined the prompt. */
    case Cancelled = 'cancelled';

    /** The key is gone, or a new face or fingerprint made it unusable. */
    case Gone = 'gone';

    /** Something else went wrong, or nothing answered; trying again is reasonable. */
    case Failed = 'failed';

    /** The device's word, or a failure where it said nothing this type recognises. */
    public static function orFailed(?string $said): self
    {
        return self::tryFrom($said ?? self::Failed->value) ?? self::Failed;
    }
}
