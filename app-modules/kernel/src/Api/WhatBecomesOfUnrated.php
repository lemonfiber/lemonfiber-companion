<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What happens to material the media server has no rating for.
 *
 * The case values are the words an invitation's answer reports it in. The
 * action is asked in two other words, `block` and `allow`, which
 * {@see self::asked()} spells: one setting, named once on the way out and once
 * on the way back, and neither spelling is this app's to change.
 */
enum WhatBecomesOfUnrated: string
{
    /** Material with no rating is held back from them. */
    case HeldBack = 'held-back';

    /** Material with no rating is let through to them. */
    case LetThrough = 'let-through';

    /** The word the `invite` action is asked with for this. */
    public function asked(): string
    {
        return match ($this) {
            self::HeldBack => 'block',
            self::LetThrough => 'allow',
        };
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.invitation.unrated.%s', $this->value);
    }
}
