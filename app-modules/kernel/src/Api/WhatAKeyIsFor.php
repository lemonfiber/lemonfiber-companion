<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What whoever minted a key said it is for.
 *
 * A declaration rather than anything the stack verified: the listing says so
 * beside it, in the stack's own words.
 */
enum WhatAKeyIsFor: string
{
    case HomeAssistant = 'home-assistant';

    case AnAssistant = 'mcp';

    case SomethingElse = 'other';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.keys.purpose.%s', $this->value);
    }
}
