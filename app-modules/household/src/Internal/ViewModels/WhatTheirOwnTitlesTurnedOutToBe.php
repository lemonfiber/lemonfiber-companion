<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * What asking for a member's own requests produced, as the rows Home leads
 * with: what has arrived for them, and what is on its way.
 *
 * Three answers, and the second must not read as the first. The house
 * answered, with rows or with none; the house could not be asked, which Home
 * says where the rows would be rather than leaving the space empty, since
 * nothing there would read as *you asked for nothing*; and this device holds
 * no session, where the shelf beside it says so already.
 */
final readonly class WhatTheirOwnTitlesTurnedOutToBe
{
    /** @param list<WhatAShelfRowSays> $rows */
    private function __construct(
        public bool $cameBack,
        public string $met,
        public string $remedy,
        public array $rows,
        private ?Obstacle $why = null,
    ) {}

    /**
     * The house answered, and these are the rows: each drawn only where it
     * holds something.
     *
     * @param list<WhatAShelfRowSays> $rows
     */
    public static function these(array $rows): self
    {
        return new self(cameBack: true, met: '', remedy: '', rows: $rows);
    }

    /** Something stood in the way, and this is what the member met. */
    public static function somethingStopped(Obstacle $why): self
    {
        return new self(cameBack: false, met: WhatAMemberIsTold::met($why), remedy: WhatAMemberIsTold::remedy($why), rows: [], why: $why);
    }

    /** This device holds no session for that house, so nothing was asked. */
    public static function theSessionEnded(): self
    {
        return new self(cameBack: false, met: '', remedy: '', rows: []);
    }

    /** Whether something stood in the way, which Home says where the rows would be. */
    public function wasStopped(): bool
    {
        return $this->met !== '';
    }

    /**
     * What the obstacle's sentences are filled with: the facts it was met with, or nothing.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return $this->why instanceof Obstacle ? WhatAnObstacleNames::in($this->why) : [];
    }
}
