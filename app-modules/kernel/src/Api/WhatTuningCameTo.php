<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking the core to open a channel for a member.
 *
 * It opened, and this is where it is served; the core says it cannot be
 * located, in its own words, as away from home; or something stood in the way
 * of asking.
 */
final readonly class WhatTuningCameTo
{
    private function __construct(private ALiveStream|Sentence|Obstacle $answer) {}

    public static function tuned(ALiveStream $stream): self
    {
        return new self($stream);
    }

    public static function cannot(Sentence $why): self
    {
        return new self($why);
    }

    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TTuned of object
     * @template TCannot of object
     * @template TRefused of object
     *
     * @param Closure(ALiveStream): TTuned $tuned
     * @param Closure(Sentence): TCannot   $cannot
     * @param Closure(Obstacle): TRefused  $refused
     *
     * @return TTuned|TCannot|TRefused
     */
    public function either(Closure $tuned, Closure $cannot, Closure $refused): object
    {
        return match (true) {
            $this->answer instanceof ALiveStream => $tuned($this->answer),
            $this->answer instanceof Sentence => $cannot($this->answer),
            default => $refused($this->answer),
        };
    }
}
