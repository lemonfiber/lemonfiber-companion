<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing the stack keeps on this machine: what it is, where, and why.
 *
 * Whether it holds a credential travels with it, so a screen cannot draw the
 * row without deciding what to say about that. Its value is not here: the
 * stack does not send one, and this has nowhere to put one.
 */
final readonly class SomethingKept
{
    private function __construct(
        private string $what,
        private string $at,
        private string $why,
        private WhetherItHoldsASecret $secret,
    ) {}

    /** One kept thing, every word of it required. */
    public static function kept(string $what, string $at, string $why, WhetherItHoldsASecret $secret): self
    {
        return new self(self::said('what', $what), self::said('at', $at), self::said('why', $why), $secret);
    }

    /** What it is, in the operator's words. */
    public function what(): string
    {
        return $this->what;
    }

    /** Where it is, in full. */
    public function where(): string
    {
        return $this->at;
    }

    /** Why it is kept. */
    public function why(): string
    {
        return $this->why;
    }

    public function secret(): WhetherItHoldsASecret
    {
        return $this->secret;
    }

    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw KeepingSaysNothing::about($field);
        }

        return $word;
    }
}
