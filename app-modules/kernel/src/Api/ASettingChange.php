<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A change to one setting the operator asked for: a value of theirs, or back to its default.
 */
final readonly class ASettingChange
{
    private function __construct(private string $setting, private ChangingASetting $how, private ASettingValue $value) {}

    /** Give the setting this value, exactly as typed; a blank setting is refused. */
    public static function toHold(string $setting, string $value): self
    {
        return new self(self::named($setting), ChangingASetting::Set, ASettingValue::value($value));
    }

    /** Put the setting back to its default; a blank setting is refused. */
    public static function backToDefault(string $setting): self
    {
        return new self(self::named($setting), ChangingASetting::Clear, ASettingValue::nothing());
    }

    /** The setting, by its name. */
    public function setting(): string
    {
        return $this->setting;
    }

    /** Which of the two changes it is. */
    public function how(): ChangingASetting
    {
        return $this->how;
    }

    /** The value it is to hold, or nothing where it goes back to its default. */
    public function value(): ASettingValue
    {
        return $this->value;
    }

    private static function named(string $setting): string
    {
        if (trim($setting) === '') {
            throw SettingSaysNothing::about('setting');
        }

        return trim($setting);
    }
}
