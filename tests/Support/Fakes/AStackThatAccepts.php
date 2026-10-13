<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheAcceptance;

/**
 * A stack that takes accepting a warning on and says what became of it, as a test arranges, remembering each check it was asked to accept.
 */
final class AStackThatAccepts implements Accepting
{
    /** The handle accepting is answered with. */
    public const string THE_ACCEPTANCE = 'an-acceptance';

    /** @var list<string> */
    private array $accepted = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheAcceptance    $accepting
     * @param Closure(Job): WhatBecameOfTheAcceptance $becoming
     */
    private function __construct(private readonly Closure $accepting, private readonly Closure $becoming) {}

    /** A stack that takes it on, and says `$became` of it once followed. */
    public static function answering(WhatBecameOfTheAcceptance $became): self
    {
        return new self(
            static fn(): WhatBecameOfTheAcceptance => WhatBecameOfTheAcceptance::underway(Job::named(self::THE_ACCEPTANCE)),
            static fn(): WhatBecameOfTheAcceptance => $became,
        );
    }

    /** A stack that answers asking with `$said` at once, taking nothing on. */
    public static function sayingAtOnce(WhatBecameOfTheAcceptance $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheAcceptance => $said,
            static fn(): WhatBecameOfTheAcceptance => $said,
        );
    }

    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheAcceptance
    {
        $this->accepted[] = $check->shown();

        return ($this->accepting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheAcceptance
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<string> every check a yes was sent for, in order */
    public function accepted(): array
    {
        return $this->accepted;
    }

    /** @return list<Job> every handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
