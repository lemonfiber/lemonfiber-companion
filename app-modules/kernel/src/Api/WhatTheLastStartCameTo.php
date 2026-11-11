<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * When the machine last started, what the check after it came to, and how long the stack has failed to come back.
 *
 * A check against the latest start is held only where the machine says when
 * that start was, so a check that has not run since the last restart is never
 * drawn as one that has.
 */
final readonly class WhatTheLastStartCameTo
{
    private function __construct(
        private WhenItSays $started,
        private TheCheckKept $sinceIt,
        private TheCheckKept $newest,
        private string $declined,
        private HowLongItHasFailed $failing,
    ) {}

    /**
     * What the stack said, refused where it holds a check against a start it gives no time for.
     *
     * `$declined` is why a run after that start started nothing, or empty where none did.
     */
    public static function said(WhenItSays $started, TheCheckKept $sinceIt, TheCheckKept $newest, string $declined, HowLongItHasFailed $failing): self
    {
        if ($sinceIt->isKept() && ! $started->isSaid()) {
            throw ARestartSaysTooLittle::againstAStartNobodyNamed();
        }

        return new self($started, $sinceIt, $newest, $declined, $failing);
    }

    /** When the machine last started, or that it will not say. */
    public function started(): WhenItSays
    {
        return $this->started;
    }

    /** The check that ran after that start, or none. */
    public function sinceIt(): TheCheckKept
    {
        return $this->sinceIt;
    }

    /** The newest check kept against any start, or none. */
    public function newest(): TheCheckKept
    {
        return $this->newest;
    }

    /** Why a run after that start started nothing, or empty where none did. */
    public function declined(): string
    {
        return $this->declined;
    }

    /** How long the stack has failed to come back, or that it is not failing. */
    public function failing(): HowLongItHasFailed
    {
        return $this->failing;
    }
}
