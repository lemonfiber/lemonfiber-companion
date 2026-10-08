<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of the five things a plugin's proof can come to.
 *
 * The four a stack says are its own words; *not asked* is what a proof with
 * no answer at all is, which the stack says by leaving the answer out.
 */
enum WhatAProofSays: string
{
    case NotAsked = 'not_asked';
    case Passed = 'passed';
    case Failed = 'failed';
    case Unproven = 'unproven';
    case FailingAsDeclared = 'failing-as-declared';

    /** What it came to, as a catalogue key. */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::NotAsked => 'plugins.proof.not_asked',
            self::Passed => 'plugins.proof.passed',
            self::Failed => 'plugins.proof.failed',
            self::Unproven => 'plugins.proof.unproven',
            self::FailingAsDeclared => 'plugins.proof.failing_as_declared',
        };
    }
}
