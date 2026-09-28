<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;

/**
 * A stack's refusal in its own words, as the template draws it.
 *
 * The one place what a refusal named leaves {@see \Modules\Kernel\Api\WhatTheRefusalNamed}
 * for a screen, so every screen drawing one draws the same three parts.
 */
final readonly class HowARefusalReads
{
    /** The sentence, what it means and what it named, the last two blank where the stack gave none. */
    public function inItsWords(ARefusalInItsWords $why): ARefusalAsShown
    {
        return new ARefusalAsShown(
            said: $why->summary(),
            meaning: $why->meaning(),
            named: $why->named()->forTheOperator(),
        );
    }
}
