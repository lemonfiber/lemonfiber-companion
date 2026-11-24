<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheJobsSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

/**
 * A stack's event stream while jobs run, as a script of what each wake of a screen hears from it.
 *
 * Every job asked about says the same in turn, or each job its own lines in
 * turn, and then nothing new, which is what the adapter answers once a job has
 * said its last line. It notes the jobs each wake asked about, how often a
 * screen let go, and how often it kept the place as it did.
 */
final class AJobThatSays implements HearingTheJobs
{
    private int $next = 0;

    /** @var array<string, int> */
    private array $nextOf = [];

    /** @var list<Job> */
    private array $asked = [];

    private int $lettingsGo = 0;

    private int $placesKept = 0;

    /**
     * @param list<WhatWasHeardFromAJob>                $everyJob what every job asked about hears, wake by wake
     * @param array<string, list<WhatWasHeardFromAJob>> $byJob    what each job hears, wake by wake, by its name
     */
    private function __construct(
        private readonly array $everyJob,
        private readonly array $byJob,
    ) {}

    /** A stream on which every job asked about says these in turn, and then nothing new. */
    public static function saying(WhatWasHeardFromAJob ...$said): self
    {
        return new self(array_values($said), []);
    }

    /**
     * A stream on which each job says its own lines in turn, and then nothing new.
     *
     * @param array<string, list<WhatWasHeardFromAJob>> $byJob
     */
    public static function eachSaying(array $byJob): self
    {
        return new self([], $byJob);
    }

    /** A stream on which no job says anything. */
    public static function nothing(): self
    {
        return new self([], []);
    }

    public function whatTheySaid(Stack $stack, Session $session, Job $job, Job ...$others): WhatTheJobsSaid
    {
        $jobs = [$job, ...array_values($others)];
        $this->asked = [...$this->asked, ...$jobs];

        if (array_key_exists($this->next, $this->everyJob)) {
            return $this->toEvery($this->everyJob[$this->next++], $jobs);
        }

        $reading = WhatTheJobsSaid::nothingNew();

        foreach ($jobs as $asked) {
            $turn = $this->nextOf[$asked->shown()] ?? 0;
            $script = $this->byJob[$asked->shown()] ?? [];

            if (array_key_exists($turn, $script)) {
                $reading = $reading->with($asked, $script[$turn]);
                $this->nextOf[$asked->shown()] = $turn + 1;
            }
        }

        return $reading;
    }

    public function letGo(): WhatTheJobsSaid
    {
        $this->lettingsGo++;

        return WhatTheJobsSaid::nothingNew();
    }

    public function letGoKeepingThePlace(): WhatTheJobsSaid
    {
        $this->placesKept++;

        return $this->letGo();
    }

    /**
     * Every job asked about, each wake asked about it, in order.
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

    /** How many of those let go keeping where the stream left off. */
    public function placesKept(): int
    {
        return $this->placesKept;
    }

    /**
     * The same heard by every job asked about, or what stood in the way of hearing any.
     *
     * @param array<Job> $jobs
     */
    private function toEvery(WhatWasHeardFromAJob $heard, array $jobs): WhatTheJobsSaid
    {
        return $heard->either(
            said: static function () use ($heard, $jobs): WhatTheJobsSaid {
                $reading = WhatTheJobsSaid::nothingNew();

                foreach ($jobs as $job) {
                    $reading = $reading->with($job, $heard);
                }

                return $reading;
            },
            nothingNew: static fn(): WhatTheJobsSaid => WhatTheJobsSaid::nothingNew(),
            met: static fn(Obstacle $why): WhatTheJobsSaid => WhatTheJobsSaid::met($why),
        );
    }
}
