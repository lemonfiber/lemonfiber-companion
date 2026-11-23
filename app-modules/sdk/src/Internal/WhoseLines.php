<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_any;

use Closure;
use Modules\Kernel\Api\AWalkFollowed;
use Modules\Kernel\Api\Job;

/** Which lines on the stream a reader takes, by the job that said each: any of some jobs', a walk's, or one job's and those no job said. */
final readonly class WhoseLines
{
    /** @param Closure(Job): bool $saidBy */
    private function __construct(
        private Closure $saidBy,
        private bool $andNobodys,
    ) {}

    public static function anyOf(Job ...$jobs): self
    {
        return new self(static fn(Job $saidBy): bool => array_any($jobs, fn(Job $job): bool => $job->is($saidBy)), andNobodys: false);
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
