<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WiringTheServices;

use function sprintf;

/**
 * A stack that answers each thing asked of it with the next answer a test gave it, and remembers what it was asked.
 *
 * {@see AStackThatInvites}' shape for a wiring run: the handle, then what the
 * run came to, each taken in turn.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatWires implements WiringTheServices
{
    /** @var list<string> each thing asked, in order: `wire` or `after:<job>` */
    private array $asked = [];

    /** @param list<WhatBecameOfTheWiring> $answers */
    private function __construct(private array $answers) {}

    /** A stack that answers with these, one per thing asked, and then with nothing it recognises. */
    public static function answering(WhatBecameOfTheWiring ...$answers): self
    {
        return new self(array_values($answers));
    }

    /** A stack the operator could not reach, for the reason given, however it is asked. */
    public static function met(Obstacle $why): self
    {
        return new self([WhatBecameOfTheWiring::met($why), WhatBecameOfTheWiring::met($why)]);
    }

    /** @return list<string> */
    public function asked(): array
    {
        return $this->asked;
    }

    public function wire(Stack $stack, Session $session): WhatBecameOfTheWiring
    {
        $this->asked[] = 'wire';

        return $this->next();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWiring
    {
        $this->asked[] = sprintf('after:%s', $job->shown());

        return $this->next();
    }

    /** The next answer in line, or the stack having no outcome once they have all been given. */
    private function next(): WhatBecameOfTheWiring
    {
        return array_shift($this->answers) ?? WhatBecameOfTheWiring::ended();
    }
}
