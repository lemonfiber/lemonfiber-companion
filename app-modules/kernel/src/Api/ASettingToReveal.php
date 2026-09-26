<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One redacted setting a support bundle is to show as it is, by the name the bundle gives it.
 *
 * Named by the operator, one at a time. The stack offers no list of the
 * settings a bundle could reveal, so this is only ever a name somebody already
 * knew and typed.
 */
final readonly class ASettingToReveal
{
    private function __construct(private string $name) {}

    /** The setting called this, with the spaces around the name taken off. */
    public static function named(string $name): self
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw ASettingHasNoName::toReveal();
        }

        return new self($trimmed);
    }

    /** The name, for sending and for saying on the screen. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whether the two name the same setting. */
    public function is(self $other): bool
    {
        return $this->name === $other->name;
    }
}
