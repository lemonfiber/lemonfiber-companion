<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\ARoomGivenBackAgreed;
use Modules\Kernel\Api\GivingBackRoom;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\WhatBecameOfTheWork;

use function sprintf;

/**
 * A stack that takes a yes to giving back room on and says what became of it, as a test arranges, remembering what it was asked.
 */
final class AStackThatGivesBackRoom implements GivingBackRoom
{
    /** The handle the yes is answered with. */
    public const string THE_GIVING_BACK = 'giving-back-room';

    /** @var list<string> */
    private array $asked = [];

    /**
     * @param WhatBecameOfTheWork<TheRoomGivenBack> $given
     * @param WhatBecameOfTheWork<TheRoomGivenBack> $became
     */
    private function __construct(private readonly WhatBecameOfTheWork $given, private readonly WhatBecameOfTheWork $became) {}

    /**
     * A stack taking the yes on, and saying `$became` when it is followed.
     *
     * @param WhatBecameOfTheWork<TheRoomGivenBack> $became
     */
    public static function answering(WhatBecameOfTheWork $became): self
    {
        return new self(WhatBecameOfTheWork::underway(Job::named(self::THE_GIVING_BACK)), $became);
    }

    /**
     * A stack that answers the yes with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<TheRoomGivenBack> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self($said, $said);
    }

    /** A stack nobody should ask. */
    public static function untouched(): self
    {
        return self::sayingAtOnce(WhatBecameOfTheWork::noOutcome());
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

    public function giveBack(Stack $stack, Session $session, ARoomGivenBackAgreed $agreed): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('give-back:%s:%s', $agreed->agreement(), $agreed->category()->value);

        return $this->given;
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->became;
    }
}
