<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * What stood in the way of something a member did, in their words, and what to do about it.
 *
 * Read off the obstacle once, so every screen that draws a refusal draws it
 * the same way: the core's own sentence where it gave one, the household's
 * words otherwise.
 */
final readonly class WhatStoppedAMember
{
    private function __construct(public string $met, public ?string $remedy, private Obstacle $why) {}

    /** What the obstacle tells a member. */
    public static function by(Obstacle $why): self
    {
        return new self(WhatAMemberIsTold::met($why), WhatAMemberIsTold::remedy($why), $why);
    }

    /** This, said without what to do about it, where the screen says that beside it already. */
    public function withoutTheRemedy(): self
    {
        return new self($this->met, null, $this->why);
    }

    /** Whether the member's session has ended, which is a way back in rather than a refusal. */
    public function endsTheSession(): bool
    {
        return $this->why->meansWeAreSignedOut();
    }

    /** Whether the phone's settings are where it is put right. */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this->why->isPutRightInTheAppsSettings();
    }

    /**
     * What the sentence is filled with.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return WhatAnObstacleNames::in($this->why);
    }

    /** Whether the sentence is the core's own, shown as it came. */
    public function isInTheStacksWords(): bool
    {
        return WhatAMemberIsTold::isInTheStacksWords($this->why);
    }
}
