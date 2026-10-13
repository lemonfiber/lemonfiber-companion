<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether the operator turned notifications on, in the app's own settings.
 *
 * Off until they do: the app asks for the phone's permission only when the
 * operator turns this on, having said first why it wants it. What the phone
 * keeps is the case's name.
 */
enum Notifying
{
    case On;

    case Off;

    /** What the app does until the operator chooses: tell nobody anything. */
    public static function standard(): self
    {
        return self::Off;
    }

    /** The choice kept under this name, or the one given where no choice is. */
    public static function named(string $name, self $otherwise): self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return $otherwise;
    }
}
