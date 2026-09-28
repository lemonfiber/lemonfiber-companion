<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack holds and what it used to: each service with what it is for, and each it dropped.
 *
 * Two lists because they answer two questions. The first is *what does this
 * one do for the house*; the second is *where did the one I remember go*, and
 * an operator looking for something they remember is answered by it rather
 * than told it does not exist.
 */
final readonly class TheCatalogue
{
    private function __construct(private WhatTheServicesAreFor $services, private WhatWasDropped $dropped) {}

    /** The catalogue as the stack gave it. */
    public static function of(WhatTheServicesAreFor $services, WhatWasDropped $dropped): self
    {
        return new self($services, $dropped);
    }

    /** Every service the stack declares, in its order. */
    public function services(): WhatTheServicesAreFor
    {
        return $this->services;
    }

    /** Every service it has dropped, in the order it records them. */
    public function dropped(): WhatWasDropped
    {
        return $this->dropped;
    }
}
