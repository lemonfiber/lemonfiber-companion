<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What one run of choosing what the household may ask for reported: whether it was a rehearsal, what stood in the way of reading the household, and what the stack noted.
 */
final readonly class TheAllowanceAsChanged
{
    private function __construct(private WhetherItWasRehearsed $did, private Sentences $said, private TheObstaclesNamed $inTheWay) {}

    /** The stack's report, its notes and what stood in the way each in the order it wrote them. */
    public static function said(WhetherItWasRehearsed $did, Sentences $said, AnObstacleNamed ...$inTheWay): self
    {
        return new self($did, $said, TheObstaclesNamed::of(...$inTheWay));
    }

    /** Whether the run rehearsed the change or made it. */
    public function did(): WhetherItWasRehearsed
    {
        return $this->did;
    }

    /** What the stack noted: what the change comes to first, then anything it noticed about the household. */
    public function whatItSays(): Sentences
    {
        return $this->said;
    }

    /** What stood in the way of reading the household after the run, none where nothing did. */
    public function whatStoodInTheWay(): TheObstaclesNamed
    {
        return $this->inTheWay;
    }
}
