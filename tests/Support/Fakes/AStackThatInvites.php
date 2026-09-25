<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatWasFoundOfTheMembers;

use function sprintf;

/**
 * A stack that answers each thing asked of it with the next answer a test gave it, and remembers what it was asked.
 *
 * {@see AStackWithAFrontDoor}'s sibling, one conversation longer: every act
 * and every asking-after takes the next answer in line, so a test lays out a
 * whole exchange — the handle, then the rehearsal, then the handle, then the
 * invitation — and reads back what the screen sent at each step.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatInvites implements Inviting
{
    /** @var list<string> each thing asked, in order: `would`, `invite`, `reset` or `after:<job>` */
    private array $asked = [];

    private ?AnInvitationAskedFor $rehearsed = null;

    private ?AnInvitationAgreed $agreed = null;

    private ?SomebodyInTheHousehold $who = null;

    /** How many times who is in was read, which is how a screen that polls is caught. */
    private int $readings = 0;

    /** @param list<WhatBecameOfTheInvitation> $answers */
    private function __construct(private array $answers, private readonly WhatWasFoundOfTheMembers $members) {}

    /** A stack with nobody in yet that answers with these, one per thing asked, and then with nothing it recognises. */
    public static function answering(WhatBecameOfTheInvitation ...$answers): self
    {
        return new self(array_values($answers), WhatWasFoundOfTheMembers::found(TheMembers::of()));
    }

    /** A stack the operator could not reach, for the reason given, however it is asked. */
    public static function met(Obstacle $why): self
    {
        return new self([WhatBecameOfTheInvitation::met($why), WhatBecameOfTheInvitation::met($why), WhatBecameOfTheInvitation::met($why)], WhatWasFoundOfTheMembers::met($why));
    }

    /** The same stack, with these members in it. */
    public function holding(TheMembers $members): self
    {
        return new self($this->answers, WhatWasFoundOfTheMembers::found($members));
    }

    /** The same stack, answering the reading of who is in with this instead. */
    public function readingAs(Obstacle $why): self
    {
        return new self($this->answers, WhatWasFoundOfTheMembers::met($why));
    }

    /** How many times who is in was read. */
    public function readings(): int
    {
        return $this->readings;
    }

    public function whoIsIn(Stack $stack, Session $session): WhatWasFoundOfTheMembers
    {
        $this->readings++;

        return $this->members;
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }

    /** What the last rehearsal was asked with, or nothing where none was asked for. */
    public function rehearsedWith(): ?AnInvitationAskedFor
    {
        return $this->rehearsed;
    }

    /** What the last invitation sent was agreed to, or nothing where none was sent. */
    public function agreedTo(): ?AnInvitationAgreed
    {
        return $this->agreed;
    }

    /** Whose password was last taken off, or nobody's. */
    public function tookTheirsOff(): ?SomebodyInTheHousehold
    {
        return $this->who;
    }

    public function wouldInvite(Stack $stack, Session $session, AnInvitationAskedFor $asked): WhatBecameOfTheInvitation
    {
        $this->asked[] = 'would';
        $this->rehearsed = $asked;

        return $this->next();
    }

    public function invite(Stack $stack, Session $session, AnInvitationAgreed $agreed): WhatBecameOfTheInvitation
    {
        $this->asked[] = 'invite';
        $this->agreed = $agreed;

        return $this->next();
    }

    public function takeThePasswordOff(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheInvitation
    {
        $this->asked[] = 'reset';
        $this->who = $who;

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheInvitation
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): WhatBecameOfTheInvitation
    {
        return array_shift($this->answers) ?? WhatBecameOfTheInvitation::ended();
    }
}
