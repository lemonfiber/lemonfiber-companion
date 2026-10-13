<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The name a phone key was registered under, which revoking it names.
 *
 * Not a secret: the listing shows it, and the journal records it.
 */
final readonly class APhoneKeysName
{
    private function __construct(private string $name) {}

    /** The one place a string becomes a phone key's name; a blank one is refused. */
    public static function of(string $name): self
    {
        if (trim($name) === '') {
            throw KeySaysNothing::about('name');
        }

        return new self($name);
    }

    /** The name as the stack wrote it. */
    public function shown(): string
    {
        return $this->name;
    }
}
