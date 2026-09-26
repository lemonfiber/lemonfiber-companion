<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_any;

use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\WhatFilenamesShow;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * The choices a support bundle has, made before it is described.
 *
 * How much of the logs, whether media filenames are shown, and which redacted
 * settings are shown as they are. A trait rather than part of the screen, for
 * the twenty-method ceiling: the screen follows the bundle, and this is the
 * half that decides what it holds.
 *
 * **A setting is revealed one at a time, by name, on its own yes.** Naming it
 * asks; only {@see reveal()} adds it, and only the one named. There is no way
 * here to reveal several at once, or all of them.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesWhatABundleHolds
{
    /** The narrowest log window offered: enough for a fault that is happening now. */
    private const int FEWER_LINES = 50;

    /** The widest log window offered: enough for a fault that came and went. */
    private const int MORE_LINES = 1000;

    /** How many log lines each service contributes, one of {@see windows()}. */
    public int $lines = HowManyLines::ON_A_PHONE;

    /** Whether media filenames are shown or replaced. Replaced unless the operator asks. */
    public WhatFilenamesShow $filenames = WhatFilenamesShow::Replaced;

    /** What the operator is typing as the name of a setting to reveal. */
    public string $naming = '';

    /** The setting named and waiting for its own yes, where one is. */
    public ?string $revealing = null;

    /** The settings agreed to, each on its own. */
    public ?SettingsToReveal $revealed = null;

    /**
     * The log windows offered, fewest lines first.
     *
     * @return list<int>
     */
    public function windows(): array
    {
        return [self::FEWER_LINES, HowManyLines::ON_A_PHONE, self::MORE_LINES];
    }

    /** Take this many lines from each service, where it is one of the windows offered. */
    public function chooseLines(int $lines): void
    {
        if (array_any($this->windows(), static fn(int $window): bool => $window === $lines)) {
            $this->lines = $lines;
        }
    }

    /** Show media filenames as they are. */
    public function showFilenames(): void
    {
        $this->filenames = WhatFilenamesShow::Shown;
    }

    /** Replace media filenames with marks. */
    public function replaceFilenames(): void
    {
        $this->filenames = WhatFilenamesShow::Replaced;
    }

    /** Whether media filenames are to be shown as they are. */
    public function showsFilenames(): bool
    {
        return $this->filenames === WhatFilenamesShow::Shown;
    }

    /** Ask about revealing the setting typed, which reveals nothing yet. Silent for a blank name. */
    public function nameASetting(): void
    {
        if (trim($this->naming) === '') {
            return;
        }

        $this->revealing = ASettingToReveal::named($this->naming)->name();
        $this->naming = '';
    }

    /** Reveal the one setting named, and only that one. */
    public function reveal(): void
    {
        if ($this->revealing === null) {
            return;
        }

        $this->revealed = $this->revealedSoFar()->with(ASettingToReveal::named($this->revealing));
        $this->revealing = null;
    }

    /** Leave the setting named hidden. */
    public function keepItHidden(): void
    {
        $this->revealing = null;
    }

    /** Stop revealing a setting agreed to. Silent for a blank name. */
    public function takeBack(string $named): void
    {
        if (trim($named) === '') {
            return;
        }

        $this->revealed = $this->revealedSoFar()->without(ASettingToReveal::named($named));
    }

    /**
     * The settings agreed to, by name, in the order they were agreed.
     *
     * @return list<string>
     */
    public function revealedNames(): array
    {
        $names = [];

        foreach ($this->revealedSoFar() as $setting) {
            $names[] = $setting->name();
        }

        return $names;
    }

    /** The bundle those choices describe, not written. */
    private function chosen(): ABundleAsked
    {
        return ABundleAsked::described(HowManyLines::of($this->lines), $this->filenames, $this->revealedSoFar());
    }

    /** The settings agreed to, which is none until the first. */
    private function revealedSoFar(): SettingsToReveal
    {
        return $this->revealed ?? SettingsToReveal::none();
    }
}
