<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatTheCatalogueSaid;

/**
 * A stack where a test says what its catalogue holds, and which remembers being asked.
 *
 * {@see AStackThatNamesItsOrigins}' sibling, and it remembers the same things:
 * which stack was asked, and how many times.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatCatalogues implements Cataloguing
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatTheCatalogueSaid $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose catalogue holds this. */
    public static function with(TheCatalogue $catalogue): self
    {
        return new self(static fn(): WhatTheCatalogueSaid => WhatTheCatalogueSaid::catalogue($catalogue));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheCatalogueSaid => WhatTheCatalogueSaid::met($why));
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function describedOn(Stack $stack, Session $session): WhatTheCatalogueSaid
    {
        $this->askedAbout = $stack;
        $this->askings++;

        return ($this->answer)();
    }
}
