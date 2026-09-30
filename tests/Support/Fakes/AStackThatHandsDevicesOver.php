<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;

/**
 * A stack that answers handing a device over the same way every time, and remembers each asking.
 *
 * Not `readonly`: what it was asked is written when the asking happens.
 */
final class AStackThatHandsDevicesOver implements HandingOverADevice
{
    public const string THE_JOB = 'a-handoff-a-test-can-name';

    /** @var list<string> */
    private array $asked = [];

    /** @var list<Job> */
    private array $followed = [];

    private function __construct(
        private readonly WhatBecameOfTheHandoff $asking,
        private readonly WhatBecameOfTheHandoff $following,
    ) {}

    /** A stack that takes the asking on and, asked after it, says `$became`. */
    public static function whichTookItOn(WhatBecameOfTheHandoff $became): self
    {
        return new self(WhatBecameOfTheHandoff::underway(Job::named(self::THE_JOB)), $became);
    }

    /** A stack that answers the asking and every following with the same. */
    public static function answering(WhatBecameOfTheHandoff $always): self
    {
        return new self($always, $always);
    }

    public function handOver(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheHandoff
    {
        $this->asked[] = $who->name();

        return $this->asking;
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheHandoff
    {
        $this->followed[] = $job;

        return $this->following;
    }

    /** @return list<string> every name a hand-off was asked for, in order */
    public function asked(): array
    {
        return $this->asked;
    }

    /** @return list<Job> every handle a hand-off was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
