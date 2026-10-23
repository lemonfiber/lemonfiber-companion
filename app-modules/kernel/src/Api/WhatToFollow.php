<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/** What a trace is asked to follow: an item, by the name somebody knows it by. */
final readonly class WhatToFollow
{
    /** @param int<0, max> $season */
    private function __construct(private string $term, private int $season, private bool $narrowed) {}

    /** The term to follow; a blank one is refused, since it follows nothing. */
    public static function called(string $term): self
    {
        $trimmed = trim($term);

        if ($trimmed === '') {
            throw TheTraceSaysNothing::about('term');
        }

        return new self($trimmed, 0, narrowed: false);
    }

    /** One season of the item; a blank term, or a season below none, is refused. */
    public static function calledInSeason(string $term, int $season): self
    {
        if ($season < 0) {
            throw TheTraceSaysNothing::about('season');
        }

        return new self(self::called($term)->term, $season, narrowed: true);
    }

    /**
     * Say what happens narrowed to a season and across every one, and get back what you built.
     *
     * @template TNarrowed of object
     * @template TWhole of object
     *
     * @param Closure(int<0, max>): TNarrowed $narrowed given the season
     * @param Closure(): TWhole       $whole
     *
     * @return TNarrowed|TWhole
     */
    public function either(Closure $narrowed, Closure $whole): object
    {
        return $this->narrowed ? $narrowed($this->season) : $whole();
    }

    public function term(): string
    {
        return $this->term;
    }
}
