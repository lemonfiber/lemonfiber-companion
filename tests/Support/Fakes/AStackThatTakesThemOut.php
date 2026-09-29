<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemovingSomebody;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;

use function sprintf;

/**
 * A stack that answers each thing asked of it with the next answer a test gave it, and remembers what it was asked.
 *
 * {@see AStackThatInvites}' sibling: every act and every asking-after takes
 * the next answer in line, so a test lays out a whole exchange — the handle,
 * the reading, the handle, the removal — and reads back what the screen sent
 * at each step.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatTakesThemOut implements RemovingSomebody
{
    /** @var list<string> each thing asked, in order: `would:<name>`, `remove:<name>` or `after:<job>` */
    private array $asked = [];

    /** @param list<WhatBecameOfTheRemoval> $answers */
    private function __construct(private array $answers) {}

    /** A stack that answers with these, one per thing asked, and then with no outcome. */
    public static function answering(WhatBecameOfTheRemoval ...$answers): self
    {
        return new self(array_values($answers));
    }

    /** A stack the operator could not reach, for the reason given, however it is asked. */
    public static function met(Obstacle $why): self
    {
        return new self([WhatBecameOfTheRemoval::met($why), WhatBecameOfTheRemoval::met($why), WhatBecameOfTheRemoval::met($why)]);
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }

    public function wouldRemove(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheRemoval
    {
        $this->asked[] = sprintf('would:%s', $who->name());

        return $this->next();
    }

    public function remove(Stack $stack, Session $session, ARemovalAgreed $agreed): WhatBecameOfTheRemoval
    {
        $this->asked[] = sprintf('remove:%s', $agreed->who()->name());

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheRemoval
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): WhatBecameOfTheRemoval
    {
        return array_shift($this->answers) ?? WhatBecameOfTheRemoval::ended();
    }
}
