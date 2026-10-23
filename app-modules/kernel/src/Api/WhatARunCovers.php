<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Which checks a run asked for from Health takes: every check, or every check of one family.
 *
 * Either way the checks that disturb are included; a run narrowed to one
 * check is {@see ACheckThatDisturbs}, which the stack names for its finding.
 */
final readonly class WhatARunCovers
{
    /** The word a run of every check is spelled with where a family's would be. */
    public const string EVERY = 'every';

    private function __construct(private ?Category $family) {}

    /** Every check the stack has. */
    public static function everyCheck(): self
    {
        return new self(null);
    }

    /** Every check of one family. */
    public static function theFamily(Category $family): self
    {
        return new self($family);
    }

    /** The word this run is spelled with: {@see self::EVERY}, or the family's. */
    public function word(): string
    {
        return $this->family->value ?? self::EVERY;
    }

    /** Whether a finding is one this run would check again. */
    public function covers(Finding $finding): bool
    {
        return ! $this->family instanceof Category || $finding->category() === $this->family;
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template TEvery of object
     * @template TFamily of object
     *
     * @param Closure(): TEvery           $every
     * @param Closure(Category): TFamily  $family
     *
     * @return TEvery|TFamily
     */
    public function either(Closure $every, Closure $family): object
    {
        return $this->family instanceof Category ? $family($this->family) : $every();
    }
}
