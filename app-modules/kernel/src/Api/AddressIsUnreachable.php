<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Pairing material carried something that cannot be dialled.
 *
 * Refused where the string becomes a value rather than where a request fails,
 * because the two read completely differently to an operator: "that pairing
 * code is not right" is something they can act on, and "the stack did not
 * answer" sends them to look at a machine that was never contacted.
 *
 * The message says nothing about what arrived. `N1-R15` names a stack address
 * beside a credential and a session token as something never logged,
 * transmitted or put in a diagnostic report, and an exception message is the
 * easiest of the three to forget.
 */
final class AddressIsUnreachable extends InvalidArgumentException
{
    public static function withoutAScheme(): self
    {
        return new self('A stack address needs a scheme to be dialled, and this pairing material carried none.');
    }

    public static function blank(): self
    {
        return new self('A stack address arrived empty, so there is nowhere to reach.');
    }
}
