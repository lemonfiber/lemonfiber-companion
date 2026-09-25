<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * How far a traced item got: the furthest stage, the stages on the way, and why it stopped where it plainly has.
 *
 * The reason is empty where it is progressing or done.
 */
final readonly class HowFarItGot
{
    private function __construct(private Stage $furthest, private TheStagesItReached $stages, private string $stall) {}

    /** The furthest stage and the way there; a blank reason is refused, an empty one says nothing stopped it. */
    public static function reached(Stage $furthest, TheStagesItReached $stages, string $stall): self
    {
        if ($stall !== '' && trim($stall) === '') {
            throw TheTraceSaysNothing::about('stall');
        }

        return new self($furthest, $stages, $stall);
    }

    public function furthest(): Stage
    {
        return $this->furthest;
    }

    public function stages(): TheStagesItReached
    {
        return $this->stages;
    }

    /** Why it stopped, or empty where it has not. */
    public function stall(): string
    {
        return $this->stall;
    }
}
