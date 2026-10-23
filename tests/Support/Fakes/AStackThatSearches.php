<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingTheIndexers;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/**
 * A stack that takes a search of the indexers on and says what became of it, as a test arranges, remembering each item and season it was asked to search for.
 */
final class AStackThatSearches implements AskingTheIndexers
{
    /** The handle a search is answered with. */
    public const string THE_SEARCH = 'a-search';

    /** @var list<string> */
    private array $searched = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<WhereItGotTo>    $running
     * @param Closure(Job): WhatBecameOfTheWork<WhereItGotTo> $becoming
     */
    private function __construct(private readonly Closure $running, private readonly Closure $becoming) {}

    /**
     * A stack that takes it on, and says `$became` of it once followed.
     *
     * @param WhatBecameOfTheWork<WhereItGotTo> $became
     */
    public static function answering(WhatBecameOfTheWork $became): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_SEARCH)),
            static fn(): WhatBecameOfTheWork => $became,
        );
    }

    /**
     * A stack that answers asking with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<WhereItGotTo> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    /**
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function search(Stack $stack, Session $session, WhatToFollow $following): WhatBecameOfTheWork
    {
        $this->searched[] = $following->either(
            narrowed: static fn(int $season): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s, season %d', $following->term(), $season)),
            whole: static fn(): TheWordCarriedOut => new TheWordCarriedOut($following->term()),
        )->said;

        return ($this->running)();
    }

    /**
     * @return WhatBecameOfTheWork<WhereItGotTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<string> every item a search was sent for, with its season where it had one, in order */
    public function searched(): array
    {
        return $this->searched;
    }

    /** @return list<Job> every handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
