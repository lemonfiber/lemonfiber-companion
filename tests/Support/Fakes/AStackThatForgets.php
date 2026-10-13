<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Forgetting;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\WhatBecameOfTheWork;

/**
 * A stack that lists what removing everything would take, takes a yes on, and says what became of each, as a test arranges.
 */
final class AStackThatForgets implements Forgetting
{
    /** The handle the listing is answered with. */
    public const string THE_LISTING = 'a-listing-of-what-would-go';

    /** The handle the yes is answered with. */
    public const string THE_REMOVAL = 'a-removal-of-everything';

    private int $asked = 0;

    /** @var list<ForgettingAgreed> */
    private array $agreed = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<TheForgetting>    $listing
     * @param Closure(): WhatBecameOfTheWork<TheForgetting>    $removing
     * @param Closure(Job): WhatBecameOfTheWork<TheForgetting> $becoming
     */
    private function __construct(
        private readonly Closure $listing,
        private readonly Closure $removing,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack that takes both on, and says `$listed` of the listing and `$removed` of the yes.
     *
     * @param WhatBecameOfTheWork<TheForgetting> $listed
     * @param WhatBecameOfTheWork<TheForgetting> $removed
     */
    public static function answering(WhatBecameOfTheWork $listed, WhatBecameOfTheWork $removed): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_LISTING)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_REMOVAL)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_REMOVAL ? $removed : $listed,
        );
    }

    /**
     * A stack that answers asking either with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<TheForgetting> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatWouldGo(Stack $stack, Session $session): WhatBecameOfTheWork
    {
        $this->asked++;

        return ($this->listing)();
    }

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function forget(Stack $stack, Session $session, ForgettingAgreed $agreed): WhatBecameOfTheWork
    {
        $this->agreed[] = $agreed;

        return ($this->removing)();
    }

    /**
     * @return WhatBecameOfTheWork<TheForgetting>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        return ($this->becoming)($job);
    }

    /** How many times what would go was asked. */
    public function askedWhatWouldGo(): int
    {
        return $this->asked;
    }

    /** @return list<ForgettingAgreed> every yes sent, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }
}
