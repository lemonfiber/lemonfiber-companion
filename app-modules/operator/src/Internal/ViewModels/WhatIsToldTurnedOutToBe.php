<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what its operator is told about produced, flattened.
 *
 * The preset and its meaning are set whenever the stack answered, because the
 * value they come from refuses to be built without them; empty only where
 * nothing answered, which the template never reaches the setting for.
 */
final readonly class WhatIsToldTurnedOutToBe
{
    /**
     * @param string                 $preset     the preset in force, or empty where nothing answered
     * @param string                 $means      what it means, or empty where nothing answered
     * @param list<OneEventSetApart> $exceptions every event set apart from it
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $preset,
        public string $means,
        public array $exceptions,
    ) {}
}
