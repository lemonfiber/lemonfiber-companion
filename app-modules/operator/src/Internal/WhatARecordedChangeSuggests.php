<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What a change suggests doing instead of putting it back, carried out of a fold.
 *
 * A fold hands back an object, so the sentence travels in one. Empty is the
 * arm with nothing to suggest, and it can only be that: the value one layer
 * down refuses a blank suggestion.
 */
final readonly class WhatARecordedChangeSuggests
{
    public function __construct(public string $said) {}
}
