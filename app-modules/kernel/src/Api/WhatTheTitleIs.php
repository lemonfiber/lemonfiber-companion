<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking for one title on a member's shelf.
 *
 * The title; or that it is absent, which is the core's one answer for a title
 * outside the member's limits and for one the household does not hold, so the
 * two cannot be told apart here either; or what stood in the way.
 */
final readonly class WhatTheTitleIs
{
    private function __construct(private ?ATitle $title, private ?Obstacle $why) {}

    public static function told(ATitle $title): self
    {
        return new self($title, null);
    }

    public static function absent(): self
    {
        return new self(null, null);
    }

    public static function refused(Obstacle $why): self
    {
        return new self(null, $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TTold of object
     * @template TAbsent of object
     * @template TRefused of object
     *
     * @param Closure(ATitle): TTold      $told
     * @param Closure(): TAbsent          $absent
     * @param Closure(Obstacle): TRefused $refused
     *
     * @return TTold|TAbsent|TRefused
     */
    public function either(Closure $told, Closure $absent, Closure $refused): object
    {
        if ($this->title instanceof ATitle) {
            return $told($this->title);
        }

        return $this->why instanceof Obstacle ? $refused($this->why) : $absent();
    }
}
