<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ACertificateReplacementAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\ReplacingTheInternalCertificate;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;

use function sprintf;

/**
 * A stack that reads replacing its internal certificate, takes a yes on, and says what became of each, as a test arranges.
 *
 * It remembers what it was asked, so a test can say the yes carried the
 * reading it was given against, and that nothing was replaced without one.
 */
final class AStackThatReplacesItsCertificate implements ReplacingTheInternalCertificate
{
    /** The handle a reading is answered with. */
    public const string THE_READING = 'a-reading-of-replacing-the-certificate';

    /** The handle the yes is answered with. */
    public const string THE_REPLACEMENT = 'replacing-the-certificate';

    /** @var list<string> */
    private array $asked = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<WhatAReplacementCameTo>    $reading
     * @param Closure(): WhatBecameOfTheWork<WhatAReplacementCameTo>    $replacing
     * @param Closure(Job): WhatBecameOfTheWork<WhatAReplacementCameTo> $becoming
     */
    private function __construct(
        private readonly Closure $reading,
        private readonly Closure $replacing,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack taking both acts on, and saying `$read` of each reading and `$replaced` of the yes.
     *
     * @param WhatBecameOfTheWork<WhatAReplacementCameTo> $read
     * @param WhatBecameOfTheWork<WhatAReplacementCameTo> $replaced
     */
    public static function answering(WhatBecameOfTheWork $read, WhatBecameOfTheWork $replaced): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_READING)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_REPLACEMENT)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_REPLACEMENT ? $replaced : $read,
        );
    }

    /**
     * A stack that answers asking either act with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<WhatAReplacementCameTo> $said
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

    public function read(Stack $stack, Session $session): WhatBecameOfTheWork
    {
        $this->asked[] = 'read';

        return ($this->reading)();
    }

    public function replace(Stack $stack, Session $session, ACertificateReplacementAgreed $agreed): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('replace:%s', $agreed->agreement());

        return ($this->replacing)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return ($this->becoming)($job);
    }
}
