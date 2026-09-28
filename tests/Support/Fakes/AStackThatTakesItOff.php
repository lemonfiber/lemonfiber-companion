<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingLemonfiberOff;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhatWasFoundOfTheUninstall;
use Modules\Kernel\Api\WhichRemoval;

use function sprintf;

/**
 * A stack that reads every removal as the one reading a test gave it, answers each act with the next answer in line, and remembers what it was asked.
 *
 * {@see AStackThatInvites}' sibling, with a reading beside the work:
 * the reading is answered at once, and the removal is work to follow.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatTakesItOff implements TakingLemonfiberOff
{
    /** @var list<string> each thing asked, in order: `read:<tier>`, `take:<tier>:<agreement>:<wait>` or `after:<job>` */
    private array $asked = [];

    /** @param list<WhatBecameOfTheUninstall> $answers */
    private function __construct(private readonly WhatWasFoundOfTheUninstall $reading, private array $answers) {}

    /** A stack whose every reading is this one, answering the removal with these, one per thing asked, and then with no outcome. */
    public static function reading(AnUninstall $uninstall, WhatBecameOfTheUninstall ...$answers): self
    {
        return new self(WhatWasFoundOfTheUninstall::found($uninstall), array_values($answers));
    }

    /** A stack the operator could not reach, for the reason given, however it is asked. */
    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundOfTheUninstall::met($why), [WhatBecameOfTheUninstall::met($why), WhatBecameOfTheUninstall::met($why)]);
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }

    public function surveyed(Stack $stack, Session $session, WhichRemoval $tier): WhatWasFoundOfTheUninstall
    {
        $this->asked[] = sprintf('read:%s', $tier->value);

        return $this->reading;
    }

    public function takeItOff(Stack $stack, Session $session, AnUninstallAgreed $agreed): WhatBecameOfTheUninstall
    {
        $this->asked[] = sprintf('take:%s:%s:%s', $agreed->tier()->value, $agreed->agreement(), $agreed->waiting()->value);

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheUninstall
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): WhatBecameOfTheUninstall
    {
        return array_shift($this->answers) ?? WhatBecameOfTheUninstall::ended();
    }
}
