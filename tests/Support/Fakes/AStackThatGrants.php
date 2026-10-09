<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\WhatTheGrantCameTo;

/**
 * A stack asked for grants, answering each from a list and remembering who asked.
 *
 * `granting()` answers the grants it is given in turn, the last one again once
 * the list runs out; `refusing()` answers every ask with one obstacle.
 */
final class AStackThatGrants implements Granting
{
    /** @var list<string> */
    private array $askedBy = [];

    /** @param list<AGrant> $grants */
    private function __construct(private array $grants, private readonly ?Obstacle $why) {}

    public static function granting(AGrant $first, AGrant ...$then): self
    {
        return new self(array_values([$first, ...$then]), null);
    }

    public static function refusing(Obstacle $why): self
    {
        return new self([], $why);
    }

    public function aGrantFor(Stack $stack, Session $session, ThisDevice $device): WhatTheGrantCameTo
    {
        $this->askedBy[] = $device->shown();

        if ($this->why instanceof Obstacle) {
            return WhatTheGrantCameTo::refused($this->why);
        }

        $grant = count($this->grants) > 1 ? array_shift($this->grants) : $this->grants[0];

        return WhatTheGrantCameTo::granted($grant);
    }

    /**
     * The device id each ask came under, in order.
     *
     * @return list<string>
     */
    public function askedBy(): array
    {
        return $this->askedBy;
    }
}
