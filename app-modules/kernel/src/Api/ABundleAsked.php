<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The support bundle an operator chose: how much of the logs, whether media
 * filenames are shown, which settings are revealed, and whether it is written.
 *
 * Described first and written only on a second, separate yes. The bundle
 * written is the one described: {@see written()} keeps every choice and
 * changes only that.
 */
final readonly class ABundleAsked
{
    private function __construct(
        private HowManyLines $lines,
        private WhatFilenamesShow $filenames,
        private SettingsToReveal $revealing,
        private bool $writes,
    ) {}

    /** Those choices, described by the stack and not written. */
    public static function described(HowManyLines $lines, WhatFilenamesShow $filenames, SettingsToReveal $revealing): self
    {
        return new self($lines, $filenames, $revealing, writes: false);
    }

    /** The same choices, written. */
    public function written(): self
    {
        return new self($this->lines, $this->filenames, $this->revealing, writes: true);
    }

    /**
     * lemonfiber's name for the action, spelled once here.
     *
     * One action describes and writes, so there is nothing to choose between
     * and a single-case enum would pretend at a choice; {@see TakingAnUpdate::asked()}
     * is the same method for the same reason.
     */
    public function asked(): string
    {
        return 'support';
    }

    /** How many log lines each service contributes. */
    public function lines(): HowManyLines
    {
        return $this->lines;
    }

    /** Whether media filenames are shown or replaced. */
    public function filenames(): WhatFilenamesShow
    {
        return $this->filenames;
    }

    /** The settings shown as they are, each agreed to on its own. */
    public function revealing(): SettingsToReveal
    {
        return $this->revealing;
    }

    /** Whether the bundle is written, rather than described. */
    public function writes(): bool
    {
        return $this->writes;
    }
}
