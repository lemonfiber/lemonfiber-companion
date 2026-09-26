<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What choosing a format for music did: the format, what became of the choice, and what the music service made of it.
 *
 * Its own answer rather than a {@see TheQualityChosen}, because music is
 * carried straight to its service where a preset is only recorded: the stack
 * answers a music choice with what asking the service came to.
 */
final readonly class AFormatChoiceMade
{
    private function __construct(
        private AFormatInForce $format,
        private WhatBecameOfTheChoice $became,
        private WhatBecameOfAskingIt $applied,
    ) {}

    /** What the stack reported. */
    public static function reported(AFormatInForce $format, WhatBecameOfTheChoice $became, WhatBecameOfAskingIt $applied): self
    {
        return new self($format, $became, $applied);
    }

    /** The format chosen, with what it means and costs. */
    public function format(): AFormatInForce
    {
        return $this->format;
    }

    /** What became of the choice. */
    public function became(): WhatBecameOfTheChoice
    {
        return $this->became;
    }

    /** What became of applying it to the music service. */
    public function applied(): WhatBecameOfAskingIt
    {
        return $this->applied;
    }
}
