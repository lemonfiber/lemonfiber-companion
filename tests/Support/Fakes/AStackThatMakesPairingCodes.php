<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\MakingPairingCodes;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;

/**
 * A stack that answers asking for a pairing code the same way every time, and remembers each asking.
 *
 * Not `readonly`: what it was asked is written when the asking happens.
 */
final class AStackThatMakesPairingCodes implements MakingPairingCodes
{
    public const string THE_JOB = 'a-pairing-code-a-test-can-name';

    private int $asked = 0;

    /** @var list<Job> */
    private array $followed = [];

    private function __construct(
        private readonly WhatBecameOfThePairingCode $asking,
        private readonly WhatBecameOfThePairingCode $following,
    ) {}

    /** A stack that takes the asking on and, asked after it, says `$became`. */
    public static function whichTookItOn(WhatBecameOfThePairingCode $became): self
    {
        return new self(WhatBecameOfThePairingCode::underway(Job::named(self::THE_JOB)), $became);
    }

    /** A stack that answers the asking and every following with the same. */
    public static function answering(WhatBecameOfThePairingCode $always): self
    {
        return new self($always, $always);
    }

    public function make(Stack $stack, Session $session): WhatBecameOfThePairingCode
    {
        $this->asked++;

        return $this->asking;
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfThePairingCode
    {
        $this->followed[] = $job;

        return $this->following;
    }

    /** How many times a code was asked for. */
    public function asked(): int
    {
        return $this->asked;
    }

    /** @return list<Job> every handle a code was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
