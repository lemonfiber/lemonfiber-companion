<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;

use Closure;

/** What each job asked about said in one reading of a stack's event stream, or what stood in the way of hearing any of them. */
final readonly class WhatTheJobsSaid
{
    /** @param array<string, WhatWasHeardFromAJob> $byJob */
    private function __construct(
        private array $byJob,
        private ?Obstacle $why,
    ) {}

    public static function nothingNew(): self
    {
        return new self([], null);
    }

    public static function met(Obstacle $why): self
    {
        return new self([], $why);
    }

    /** The same reading, with what this job said in it. */
    public function with(Job $job, WhatWasHeardFromAJob $heard): self
    {
        return new self([...$this->byJob, $job->shown() => $heard], $this->why);
    }

    /** What this job said in the reading: its lines, nothing new, or what stood in the way of hearing it. */
    public function from(Job $job): WhatWasHeardFromAJob
    {
        if ($this->why instanceof Obstacle) {
            return WhatWasHeardFromAJob::met($this->why);
        }

        return array_key_exists($job->shown(), $this->byJob) ? $this->byJob[$job->shown()] : WhatWasHeardFromAJob::nothingNew();
    }

    /**
     * @template T of object
     * @param Closure(self): T     $heard
     * @param Closure(Obstacle): T $met
     * @return T
     */
    public function either(Closure $heard, Closure $met): object
    {
        return $this->why instanceof Obstacle ? $met($this->why) : $heard($this);
    }
}
