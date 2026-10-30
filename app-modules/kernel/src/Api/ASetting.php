<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One setting a stack's services read: its name, whose it is, who reads it, the operator's value where one is recorded, and the values it accepts where it lists them.
 */
final readonly class ASetting
{
    private function __construct(
        private string $name,
        private WhoseASettingIs $whose,
        private TheServicesReadingIt $readers,
        private ASettingValue $value,
        private TheChoices $choices,
    ) {}

    /** The setting; a blank name is refused. */
    public static function read(string $name, WhoseASettingIs $whose, TheServicesReadingIt $readers, ASettingValue $value): self
    {
        if (trim($name) === '') {
            throw SettingSaysNothing::about('name');
        }

        return new self(trim($name), $whose, $readers, $value, TheChoices::none());
    }

    /** The same setting, accepting only these values. */
    public function among(TheChoices $choices): self
    {
        return new self($this->name, $this->whose, $this->readers, $this->value, $choices);
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

    /** The values it accepts, or none where its services take any value. */
    public function choices(): TheChoices
    {
        return $this->choices;
    }

    /** Whether its choices hang on the other setting, or the other's choices hang on it, so moving one can leave the other outside its choices. */
    public function hangsTogetherWith(self $other): bool
    {
        return $this->name !== $other->name
            && ($this->choices->hangOn($other) || $other->choices->hangOn($this));
    }
}
