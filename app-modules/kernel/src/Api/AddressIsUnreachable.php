<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

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

    /**
     * A scheme arrived, and it is not one a stack is dialled over.
     *
     * The scheme itself is not quoted back. It is a smaller thing to leak than
     * a host and it is still part of what pairing material carried, and the
     * rule this class is written to is about the material rather than about how
     * much of it a line gives away.
     *
     * The vocabulary in the sentence is read from {@see Scheme} rather than
     * typed beside it. A scheme added to the enum and not to this message would
     * leave an operator being told their address is impossible by a sentence
     * that no longer lists the way they were told to reach their stack.
     */
    public static function withAnUnknownScheme(): self
    {
        return new self(sprintf(
            'A stack is dialled over %s, and this pairing material asked for something else.',
            implode(' or ', array_map(static fn(Scheme $scheme): string => $scheme->value, Scheme::cases())),
        ));
    }

    public static function blank(): self
    {
        return new self('A stack address arrived empty, so there is nowhere to reach.');
    }
}
