<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\HearingAJob;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

/**
 * A stack's event stream while a job runs, as a script of what each wake of a screen hears from it.
 *
 * It says these in turn and then nothing new, which is what the adapter answers
 * once the job has said its last line. It notes the job each wake asked about,
 * and how often a screen let go.
 */
final class AJobThatSays implements HearingAJob
{
    private int $next = 0;

    /** @var list<Job> */
    private array $asked = [];

    private int $lettingsGo = 0;

    /** @param list<WhatWasHeardFromAJob> $script */
    private function __construct(private readonly array $script) {}

    /** A stream on which the job says these in turn, and then nothing new. */
    public static function saying(WhatWasHeardFromAJob ...$said): self
    {
        return new self(array_values($said));
    }

    /** A stream on which no job says anything. */
    public static function nothing(): self
    {
        return new self([]);
    }

    public function whatItSaid(Stack $stack, Session $session, Job $job): WhatWasHeardFromAJob
    {
        $this->asked[] = $job;

        if (! array_key_exists($this->next, $this->script)) {
            return WhatWasHeardFromAJob::nothingNew();
        }

        return $this->script[$this->next++];
    }

    public function letGo(): WhatWasHeardFromAJob
    {
        $this->lettingsGo++;

        return WhatWasHeardFromAJob::nothingNew();
    }

    /**
     * The job each wake asked about, in order.
     *
     * @return list<Job>
     */
    public function asked(): array
    {
        return $this->asked;
    }

    /** How many times a screen let go of the subscription. */
    public function lettingsGo(): int
    {
        return $this->lettingsGo;
    }
}
