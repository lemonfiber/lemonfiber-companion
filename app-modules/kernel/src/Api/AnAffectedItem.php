<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One thing the health summary counts as wrong, and what to do about it.
 *
 * Everything the core says about it, and nothing the app adds: which check
 * raised it, how bad it is, what is wrong in one line, what that costs the
 * operator, what to try, most likely first, and what else is wrong because of
 * it — and, where it is a service that stopped, the code it exited on, which
 * is for whoever helps rather than for the card.
 */
final readonly class AnAffectedItem
{
    private function __construct(
        private Check $check,
        private Severity $severity,
        private string $summary,
        private string $meaning,
        private Remedies $remedies,
        private WhatFollowedFromIt $downstream,
        private ?int $exit = null,
    ) {}

    public static function of(
        Check $check,
        Severity $severity,
        string $summary,
        string $meaning,
        Remedies $remedies,
        WhatFollowedFromIt $downstream,
    ): self {
        return new self($check, $severity, $summary, $meaning, $remedies, $downstream);
    }

    /** An item about a service that exited on this code. */
    public static function ofAServiceThatExited(
        Check $check,
        Severity $severity,
        string $summary,
        string $meaning,
        Remedies $remedies,
        WhatFollowedFromIt $downstream,
        int $code,
    ): self {
        return new self($check, $severity, $summary, $meaning, $remedies, $downstream, $code);
    }

    /**
     * Say the code the service exited on, or that the item names none.
     *
     * Two arms for {@see Daemon::exit()}'s reason: a null would reach a screen
     * as an empty place where a number belongs.
     *
     * @template TCode of object
     * @template TUnstated of object
     *
     * @param  Closure(int): TCode  $said
     * @param  Closure(): TUnstated $unstated
     * @return TCode|TUnstated
     */
    public function exit(Closure $said, Closure $unstated): object
    {
        return $this->exit === null ? $unstated() : $said($this->exit);
    }

    public function check(): Check
    {
        return $this->check;
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function meaning(): string
    {
        return $this->meaning;
    }

    public function remedies(): Remedies
    {
        return $this->remedies;
    }

    public function downstream(): WhatFollowedFromIt
    {
        return $this->downstream;
    }
}
