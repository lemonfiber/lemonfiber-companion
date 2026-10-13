<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why this phone holds no key that proves its operator, in the device's own word.
 *
 * Mirrors `PhoneKeyRule.kt` and `PhoneKeyRule.swift`.
 */
enum WhyNoPhoneKey: string
{
    /** The phone has nowhere in hardware to keep one. */
    case NoHardware = 'no_hardware';

    /** No face or fingerprint is set up, so nothing could guard one. */
    case NoBiometric = 'no_biometric';

    /** The phone made it in software, so it was thrown away. */
    case InSoftware = 'in_software';

    /** The phone would not attest where it made it. */
    case Unattested = 'unattested';

    /** Something else went wrong; trying again is reasonable. */
    case Failed = 'failed';

    /**
     * The device's word, or `$silence` where it said nothing this type recognises.
     *
     * A machine that is not a handset says nothing at all, and what that comes to differs by
     * question: asking where a key could live, it has nowhere; asking for one to be made, the
     * making failed.
     */
    public static function or(self $silence, ?string $said): self
    {
        if ($said === null) {
            return $silence;
        }

        return self::tryFrom($said) ?? self::Failed;
    }
}
