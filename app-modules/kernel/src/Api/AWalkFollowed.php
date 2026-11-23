<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The walk a screen follows the steps of: one the stack named, or one it has not named yet. */
final readonly class AWalkFollowed
{
    private function __construct(private ?Job $job) {}

    public static function notNamedYet(): self
    {
        return new self(null);
    }

    public static function named(Job $job): self
    {
        return new self($job);
    }

    /** Whether a step this job said is this walk's: none is, before the stack has named it. */
    public function saidBy(Job $job): bool
    {
        return $this->job instanceof Job && $this->job->is($job);
    }
}
