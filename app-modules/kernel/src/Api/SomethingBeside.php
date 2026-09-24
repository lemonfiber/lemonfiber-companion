<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something on this machine the stack neither keeps nor would remove.
 *
 * The library is the operator's and the containers are the engine's. Named
 * beside what is kept, because somebody reading a list of what the stack holds
 * is owed the reason the thing they care about most is not on it.
 */
final readonly class SomethingBeside
{
    private function __construct(private string $what, private string $why) {}

    /** What it is, and whose it is. */
    public static function named(string $what, string $why): self
    {
        return new self(self::said('what', $what), self::said('why', $why));
    }

    public function what(): string
    {
        return $this->what;
    }

    /** Whose it is, and why it is not the stack's to take away. */
    public function why(): string
    {
        return $this->why;
    }

    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw KeepingSaysNothing::about($field);
        }

        return $word;
    }
}
