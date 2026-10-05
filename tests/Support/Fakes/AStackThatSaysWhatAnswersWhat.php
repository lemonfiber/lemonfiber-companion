<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Linking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\WhatTheLinksSaid;

/**
 * A stack where a test says what it wires to what, and which remembers being asked.
 *
 * {@see AStackThatCatalogues}' sibling, and it remembers the same things:
 * which stack was asked, and how many times.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysWhatAnswersWhat implements Linking
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatTheLinksSaid $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack that wires this to what. */
    public static function with(TheLinks $links): self
    {
        return new self(static fn(): WhatTheLinksSaid => WhatTheLinksSaid::links($links));
    }

    /** A stack that answered and could not read its own wiring, and says why. */
    public static function refusing(ARefusalInItsWords $why): self
    {
        return new self(static fn(): WhatTheLinksSaid => WhatTheLinksSaid::refused($why));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheLinksSaid => WhatTheLinksSaid::met($why));
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

    public function linkedOn(Stack $stack, Session $session): WhatTheLinksSaid
    {
        $this->askedAbout = $stack;
        $this->askings++;

        return ($this->answer)();
    }
}
