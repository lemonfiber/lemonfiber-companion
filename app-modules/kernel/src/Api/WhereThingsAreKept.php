<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A directory everything the stack keeps sits under, and what losing it costs.
 *
 * There are two, and nothing the stack keeps is outside them — which is what
 * makes *everything it keeps* a claim somebody can check rather than one they
 * have to trust.
 */
final readonly class WhereThingsAreKept
{
    private function __construct(private string $at, private string $what) {}

    /** The directory, and what lives under it. */
    public static function at(string $at, string $what): self
    {
        return new self(self::said('at', $at), self::said('what', $what));
    }

    /** The directory itself, in full. */
    public function where(): string
    {
        return $this->at;
    }

    /** What lives under it, and what losing it would cost. */
    public function what(): string
    {
        return $this->what;
    }

    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw KeepingSaysNothing::about($field);
        }

        return $word;
    }
}
