<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;

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
     * @param Closure(): WhatBecameOfTheWork<Report>    $accepting
     * @param Closure(Job): WhatBecameOfTheWork<Report> $becoming
     */
    private function __construct(private readonly Closure $accepting, private readonly Closure $becoming) {}

    /**
     * A stack that takes it on, and says `$became` of it once followed.
     *
     * @param WhatBecameOfTheWork<Report> $became
     */
    public static function answering(WhatBecameOfTheWork $became): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_ACCEPTANCE)),
            static fn(): WhatBecameOfTheWork => $became,
        );
    }

    /**
     * A stack that answers asking with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<Report> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheWork
    {
        $this->accepted[] = $check->shown();

        return ($this->accepting)();
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
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
