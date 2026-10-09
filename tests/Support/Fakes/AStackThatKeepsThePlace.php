<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\KeepingThePlace;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\WhatThePlaceCameTo;

use function sprintf;

/**
 * A stack told where members are, remembering each place as one line.
 *
 * `keeping()` keeps every place it is told, and answers it as kept;
 * `refusing()` keeps none, for one reason, and remembers them all the same,
 * since they were told.
 */
final class AStackThatKeepsThePlace implements KeepingThePlace
{
    /** @var list<string> */
    private array $told = [];

    private function __construct(private readonly ?Obstacle $why) {}

    public static function keeping(): self
    {
        return new self(null);
    }

    public static function refusing(Obstacle $why): self
    {
        return new self($why);
    }

    public function keep(Stack $stack, Session $session, ThePlace $place): WhatThePlaceCameTo
    {
        $this->told[] = sprintf(
            $place->isTheEnd() ? '%s at %d, the end' : '%s at %d',
            $place->holding()->named(),
            $place->howFarIn()->seconds(),
        );

        return $this->why instanceof Obstacle ? WhatThePlaceCameTo::refused($this->why) : WhatThePlaceCameTo::kept($place);
    }

    /**
     * Every place it was told, in order, as `<id> at <seconds>` and `, the end` where it was.
     *
     * @return list<string>
     */
    public function told(): array
    {
        return $this->told;
    }
}
