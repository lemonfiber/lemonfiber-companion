<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What installing a plugin changes on the machine: every file it writes, every ask it leaves contested, every bundled setting it overrides, and every service that takes a privileged shape.
 *
 * The same before and after the yes; what the proofs and the stack's checks
 * came to is the install's own.
 */
final readonly class WhatAnInstallChanges
{
    private function __construct(
        private ThePluginChanges $changes,
        private TheContestsLeft $contests,
        private TheSettingsItOverrides $overrides,
        private TheShapesTaken $taking,
        private TheGrantsAsked $granting,
        private TheUpstreamsWritten $sharing,
    ) {}

    /** These, as the stack listed them. */
    public static function these(ThePluginChanges $changes, TheContestsLeft $contests, TheSettingsItOverrides $overrides, TheShapesTaken $taking, TheGrantsAsked $granting, TheUpstreamsWritten $sharing): self
    {
        return new self($changes, $contests, $overrides, $taking, $granting, $sharing);
    }

    /** Every change, in order. */
    public function changes(): ThePluginChanges
    {
        return $this->changes;
    }

    /** Every ask it would leave contested. */
    public function contests(): TheContestsLeft
    {
        return $this->contests;
    }

    /** Every bundled setting it changes. */
    public function overrides(): TheSettingsItOverrides
    {
        return $this->overrides;
    }

    /** Every service taking a privileged shape. */
    public function taking(): TheShapesTaken
    {
        return $this->taking;
    }

    /** Every credential of another service it would hand the plugin. */
    public function granting(): TheGrantsAsked
    {
        return $this->granting;
    }

    /** Every adapter that would write the files of the service it fronts. */
    public function sharing(): TheUpstreamsWritten
    {
        return $this->sharing;
    }
}
