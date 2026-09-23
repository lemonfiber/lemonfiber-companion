<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What the operator will be told about: the preset in force, what it means,
 * and every event set apart from it.
 *
 * The preset and its meaning are the stack's words, and both are required: a
 * preset named without what it means is a word somebody has to look up before
 * they know whether they will be woken at three.
 *
 * **What a call to change it came to is not here.** The stack's answer also
 * says whether a call changed anything and whether it only rehearsed; this app
 * reads the setting and never changes it, so neither describes anything it
 * did.
 */
final readonly class WhatTheOperatorIsTold
{
    private function __construct(
        private string $preset,
        private string $means,
        private SetApart $exceptions,
    ) {}

    /** The preset in force, what it means, and what is set apart from it. */
    public static function byPreset(string $preset, string $means, SetApart $exceptions): self
    {
        if (trim($preset) === '') {
            throw AlertSaysNothing::about('preset');
        }

        if (trim($means) === '') {
            throw AlertSaysNothing::about('means');
        }

        return new self($preset, $means, $exceptions);
    }

    /** The preset in force for events with no exception of their own. */
    public function preset(): string
    {
        return $this->preset;
    }

    /** What that preset means, in the operator's terms. */
    public function means(): string
    {
        return $this->means;
    }

    /** Every event set apart from it. */
    public function exceptions(): SetApart
    {
        return $this->exceptions;
    }
}
