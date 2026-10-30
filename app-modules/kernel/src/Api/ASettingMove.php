<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One setting a change moves: what it held, what it is to hold, and what moving it costs.
 */
final readonly class ASettingMove
{
    private function __construct(private string $setting, private ASettingValue $was, private ASettingValue $value, private string $consequence) {}

    /** As the stack wrote it; a blank setting is refused, and what it costs is blank where it costs nothing. */
    public static function said(string $setting, ASettingValue $was, ASettingValue $value, string $consequence): self
    {
        if (trim($setting) === '') {
            throw SettingSaysNothing::about('setting');
        }

        return new self(trim($setting), $was, $value, trim($consequence));
    }

    /** The setting, by its name. */
    public function setting(): string
    {
        return $this->setting;
    }

    /** What it held before. */
    public function was(): ASettingValue
    {
        return $this->was;
    }

    /** What it is to hold, or nothing where it goes back to its default. */
    public function value(): ASettingValue
    {
        return $this->value;
    }

    /** What moving it costs, in the stack's words, or blank where it costs nothing. */
    public function consequence(): string
    {
        return $this->consequence;
    }
}
