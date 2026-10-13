<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Forgetting;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfForgetting;

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
     * @param Closure(): WhatBecameOfForgetting    $listing
     * @param Closure(): WhatBecameOfForgetting    $removing
     * @param Closure(Job): WhatBecameOfForgetting $becoming
     */
    private function __construct(
        private readonly Closure $listing,
        private readonly Closure $removing,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes both on, and says `$listed` of the listing and `$removed` of the yes. */
    public static function answering(WhatBecameOfForgetting $listed, WhatBecameOfForgetting $removed): self
    {
        return new self(
            static fn(): WhatBecameOfForgetting => WhatBecameOfForgetting::underway(Job::named(self::THE_LISTING)),
            static fn(): WhatBecameOfForgetting => WhatBecameOfForgetting::underway(Job::named(self::THE_REMOVAL)),
            static fn(Job $job): WhatBecameOfForgetting => $job->shown() === self::THE_REMOVAL ? $removed : $listed,
        );
    }

    /** A stack that answers asking either with `$said` at once, taking nothing on. */
    public static function sayingAtOnce(WhatBecameOfForgetting $said): self
    {
        return new self(
            static fn(): WhatBecameOfForgetting => $said,
            static fn(): WhatBecameOfForgetting => $said,
            static fn(): WhatBecameOfForgetting => $said,
        );
    }

    public function whatWouldGo(Stack $stack, Session $session): WhatBecameOfForgetting
    {
        $this->asked++;

        return ($this->listing)();
    }

    public function forget(Stack $stack, Session $session, ForgettingAgreed $agreed): WhatBecameOfForgetting
    {
        $this->agreed[] = $agreed;

        return ($this->removing)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfForgetting
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
