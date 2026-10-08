<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the stack's own checks made of an install: every check it made worse, and every one nothing could be concluded about.
 *
 * Unsettled is said and never acted on: *could not tell* is not *broke*. A
 * rehearsal, and an install whose proofs did not hold, asks the checks
 * nothing, which is its own answer rather than an empty list.
 */
final readonly class WhatTheChecksMade
{
    private function __construct(private bool $asked, private PluginLines $broke, private PluginLines $unsettled) {}

    /** The checks were asked, and these are what they made of it, each by its title. */
    public static function of(PluginLines $broke, PluginLines $unsettled): self
    {
        return new self(asked: true, broke: $broke, unsettled: $unsettled);
    }

    /** The checks were not asked. */
    public static function notAsked(): self
    {
        return new self(asked: false, broke: PluginLines::none(), unsettled: PluginLines::none());
    }

    /** Whether the checks were asked at all. */
    public function wereAsked(): bool
    {
        return $this->asked;
    }

    /** Every check the install made worse, by title. */
    public function broke(): PluginLines
    {
        return $this->broke;
    }

    /** Every check nothing could be concluded about, by title. */
    public function unsettled(): PluginLines
    {
        return $this->unsettled;
    }

    /** Whether the checks were asked and the install broke nothing they check. */
    public function brokeNothing(): bool
    {
        return $this->asked && $this->broke->isEmpty();
    }
}
