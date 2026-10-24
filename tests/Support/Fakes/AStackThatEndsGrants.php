<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevokeAgreed;
use Modules\Kernel\Api\EndingACredentialGrant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;

use function sprintf;

/**
 * A stack that reads ending a grant, takes a yes on, and says what became of each, as a test arranges.
 *
 * It remembers what it was asked, so a test can say the yes carried the
 * reading it was given against, and that nothing was ended without one.
 */
final class AStackThatEndsGrants implements EndingACredentialGrant
{
    /** The handle the reading is answered with. */
    public const string THE_READING = 'a-reading-of-ending-a-grant';

    /** The handle the yes is answered with. */
    public const string THE_END = 'ending-a-grant';

    /** @var list<string> */
    private array $asked = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<WhatARevokeCameTo>    $reading
     * @param Closure(): WhatBecameOfTheWork<WhatARevokeCameTo>    $ending
     * @param Closure(Job): WhatBecameOfTheWork<WhatARevokeCameTo> $becoming
     */
    private function __construct(
        private readonly Closure $reading,
        private readonly Closure $ending,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack taking both acts on, and saying `$read` of the reading and `$ended` of the yes.
     *
     * @param WhatBecameOfTheWork<WhatARevokeCameTo> $read
     * @param WhatBecameOfTheWork<WhatARevokeCameTo> $ended
     */
    public static function answering(WhatBecameOfTheWork $read, WhatBecameOfTheWork $ended): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_READING)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_END)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_END ? $ended : $read,
        );
    }

    /**
     * A stack that answers asking either act with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<WhatARevokeCameTo> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(static fn(): WhatBecameOfTheWork => $said, static fn(): WhatBecameOfTheWork => $said, static fn(): WhatBecameOfTheWork => $said);
    }

    /** A stack nobody should ask. */
    public static function untouched(): self
    {
        return self::sayingAtOnce(WhatBecameOfTheWork::ended());
    }

    /**
     * Everything it was asked, in order.
     *
     * @return list<string>
     */
    public function asked(): array
    {
        return $this->asked;
    }

    public function read(Stack $stack, Session $session, ACredentialGrant $grant): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('read:%s:%s', $grant->plugin()->named(), $grant->credential());

        return ($this->reading)();
    }

    public function end(Stack $stack, Session $session, ACredentialRevokeAgreed $agreed): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('end:%s:%s:%s', $agreed->grant()->plugin()->named(), $agreed->grant()->credential(), $agreed->agreement());

        return ($this->ending)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return ($this->becoming)($job);
    }
}
