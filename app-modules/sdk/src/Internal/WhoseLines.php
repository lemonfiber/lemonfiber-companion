<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Closure;
use Modules\Kernel\Api\AWalkFollowed;
use Modules\Kernel\Api\Job;

/** Which lines on the stream a reader takes, by the job that said each: one job's, a walk's, or one job's and those no job said. */
final readonly class WhoseLines
{
    /** @param Closure(Job): bool $saidBy */
    private function __construct(
        private Closure $saidBy,
        private bool $andNobodys,
    ) {}

    public static function onlyOf(Job $job): self
    {
        return new self(static fn(Job $saidBy): bool => $job->is($saidBy), andNobodys: false);
    }

    public static function ofTheWalk(AWalkFollowed $walk): self
    {
        return new self(static fn(Job $saidBy): bool => $walk->saidBy($saidBy), andNobodys: false);
    }

    public static function ofOrOfNoJob(Job $job): self
    {
        return new self(static fn(Job $saidBy): bool => $job->is($saidBy), andNobodys: true);
    }

    public function take(?string $saidBy): bool
    {
        return $saidBy === null ? $this->andNobodys : ($this->saidBy)(Job::named($saidBy));
    }
}
