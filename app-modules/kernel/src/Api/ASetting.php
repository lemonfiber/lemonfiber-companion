<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One setting a stack's services read: its name, whose it is, who reads it, and the operator's value where one is recorded.
 */
final readonly class ASetting
{
    private function __construct(
        private string $name,
        private WhoseASettingIs $whose,
        private TheServicesReadingIt $readers,
        private ASettingValue $value,
    ) {}

    /** The setting; a blank name is refused. */
    public static function read(string $name, WhoseASettingIs $whose, TheServicesReadingIt $readers, ASettingValue $value): self
    {
        if (trim($name) === '') {
            throw SettingSaysNothing::about('name');
        }

        return new self(trim($name), $whose, $readers, $value);
    }

    /** Its name, which changing it names. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whose it is. */
    public function whose(): WhoseASettingIs
    {
        return $this->whose;
    }

    /** Each service that reads it. */
    public function readers(): TheServicesReadingIt
    {
        return $this->readers;
    }

    /** The operator's value, or nothing where it is left as it came. */
    public function value(): ASettingValue
    {
        return $this->value;
    }
}
