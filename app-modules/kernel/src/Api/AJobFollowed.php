<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A job a screen follows: its name, whether it ran when last asked after, and what it has said. */
final readonly class AJobFollowed
{
    private function __construct(
        public Job $job,
        public bool $running,
        public WhatAJobSaid $said,
    ) {}

    public static function named(Job $job): self
    {
        return new self($job, running: true, said: WhatAJobSaid::nothing());
    }

    /** The job, no longer running when last asked after. */
    public function finished(): self
    {
        return new self($this->job, running: false, said: $this->said);
    }

    /** The job, running or not as it was when last asked after: one that met an obstacle can be found running again. */
    public function runningWhenAsked(bool $running): self
    {
        return new self($this->job, running: $running, said: $this->said);
    }

    public function heard(WhatWasHeardFromAJob $heard): self
    {
        return new self($this->job, running: $this->running, said: $this->said->heard($heard));
    }
}
