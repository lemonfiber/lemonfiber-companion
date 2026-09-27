<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What handing a support bundle over came to, as two catalogue keys, carried out of an `either()` arm.
 *
 * What happened, and what stands after it. A sheet that was reached leaves the
 * choice with the operator; anything else leaves nothing gone from the phone
 * and the bundle where the stack wrote it. Each arm says both, so the second
 * line cannot be drawn under the wrong first one.
 *
 * `Internal` for {@see WhatTheSharingDid}'s reason.
 */
final readonly class WhatHandingOverCameTo
{
    private function __construct(public string $said, public string $leaves) {}

    /** The sheet was put in front of the operator, and where it goes is theirs. */
    public static function offered(): self
    {
        return new self('stacks.help.handed_over', 'stacks.help.yours_to_send');
    }

    /** It was not, for the reason that catalogue key says, and nothing left the phone. */
    public static function stoppedBy(string $said): self
    {
        return new self($said, 'stacks.help.still_on_the_machine');
    }

    /** Nothing was asked, so there is nothing to say. */
    public static function nothing(): self
    {
        return new self('', '');
    }
}
