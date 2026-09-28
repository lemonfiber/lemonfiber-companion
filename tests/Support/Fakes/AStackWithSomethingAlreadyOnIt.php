<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Closure;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\WhatBecameOfTheMove;
use Modules\Kernel\Api\WhatWasFoundAlreadyHere;

use function sprintf;

/**
 * A stack where a test says what is already on its machine and what each move comes to, and which remembers being asked.
 *
 * {@see AStackWithAFrontDoor}'s sibling one endpoint along, and
 * {@see AStackThatInvites}' for the acts: every act and every asking-after
 * takes the next answer in line, so a test lays out a whole exchange and
 * reads back what the screen sent at each step.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackWithSomethingAlreadyOnIt implements MovingIn
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @var list<string> each act asked, in order: `would:<mode>`, `move:<mode>` or `after:<job>` */
    private array $acts = [];

    /**
     * @param Closure(): WhatWasFoundAlreadyHere $answer
     * @param list<WhatBecameOfTheMove>          $moves
     */
    private function __construct(private readonly Closure $answer, private array $moves = []) {}

    /** A stack whose survey found this. */
    public static function with(TheSurvey $survey): self
    {
        return new self(static fn(): WhatWasFoundAlreadyHere => WhatWasFoundAlreadyHere::found($survey));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatWasFoundAlreadyHere => WhatWasFoundAlreadyHere::met($why),
            [WhatBecameOfTheMove::met($why), WhatBecameOfTheMove::met($why), WhatBecameOfTheMove::met($why)],
        );
    }

    /** The same stack, answering each act asked of it with these in turn, and then with nothing it recognises. */
    public function moving(WhatBecameOfTheMove ...$moves): self
    {
        return new self($this->answer, array_values($moves));
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** How many times it was asked, which catches a screen asking twice a frame. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** Whether it was handed a session with something in it. */
    public function wasGivenASession(): bool
    {
        return $this->carried;
    }

    /** @return list<string> */
    public function acts(): array
    {
        return $this->acts;
    }

    public function surveyedOn(Stack $stack, Session $session): WhatWasFoundAlreadyHere
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }

    public function wouldMoveIn(Stack $stack, Session $session, MovingInBy $by): WhatBecameOfTheMove
    {
        $this->acts[] = sprintf('would:%s', $by->value);

        return $this->next();
    }

    public function moveIn(Stack $stack, Session $session, AMoveAgreed $agreed): WhatBecameOfTheMove
    {
        $this->acts[] = sprintf('move:%s', $agreed->by()->value);

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheMove
    {
        $this->acts[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): WhatBecameOfTheMove
    {
        return array_shift($this->moves) ?? WhatBecameOfTheMove::ended();
    }
}
