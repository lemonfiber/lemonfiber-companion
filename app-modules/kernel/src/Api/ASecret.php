<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One secret a stack's services read, never with its value: its name, whose it is, who reads it, whether a value is held and where it came from.
 */
final readonly class ASecret
{
    private function __construct(
        private string $name,
        private WhoseASettingIs $whose,
        private TheServicesReadingIt $readers,
        private bool $held,
        private WhereASecretCameFrom $from,
    ) {}

    /** The secret; a blank name is refused. */
    public static function read(string $name, WhoseASettingIs $whose, TheServicesReadingIt $readers, bool $held, WhereASecretCameFrom $from): self
    {
        if (trim($name) === '') {
            throw SettingSaysNothing::about('name');
        }

        return new self(trim($name), $whose, $readers, $held, $from);
    }

    /** Its name. */
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

    /** Whether a value is held. */
    public function isHeld(): bool
    {
        return $this->held;
    }

    /** Where its value came from. */
    public function from(): WhereASecretCameFrom
    {
        return $this->from;
    }
}
